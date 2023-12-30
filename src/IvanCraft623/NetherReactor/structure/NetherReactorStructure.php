<?php

/*
 *   _   _      _   _               _____                 _
 *  | \ | |    | | | |             |  __ \               | |
 *  |  \| | ___| |_| |__   ___ _ __| |__) |___  __ _  ___| |_ ___  _ __
 *  | . ` |/ _ \ __| '_ \ / _ \ '__|  _  // _ \/ _` |/ __| __/ _ \| '__|
 *  | |\  |  __/ |_| | | |  __/ |  | | \ \  __/ (_| | (__| || (_) | |
 *  |_| \_|\___|\__|_| |_|\___|_|  |_|  \_\___|\__,_|\___|\__\___/|_|
 *
 * A PocketMine-MP plugin that implements the nether reactor.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author IvanCraft623
 */

declare(strict_types=1);

namespace IvanCraft623\NetherReactor\structure;

use IvanCraft623\NetherReactor\NetherReactor;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\StringToItemParser;
use pocketmine\math\Vector3;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\Position;
use pocketmine\world\World;
use function array_rand;
use function count;
use function file_get_contents;
use function intdiv;
use function is_array;
use function is_float;
use function is_int;
use function is_string;
use function json_decode;
use function max;
use function min;
use function mt_rand;
use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;

/*
 * All values are referencded from the position of the nether reactor core.
 */
final class NetherReactorStructure{
	use SingletonTrait;

	public const DEFAULT_MAX_BOUND_Y = 30;
	public const DEFAULT_MIN_BOUND_Y = -3;

	private int $maxY;
	private int $minY;

	private Vector3 $minRoomPosition;
	private Vector3 $maxRoomPosition;

	/** @var Vector3[] */
	private array $spireBlocks = [];

	private Block $spireMaterial;

	/** @var Platform[] */
	private array $platforms = [];

	/** @var PatternLayer[] */
	private array $patternLayers = [];

	public function __construct() {
		$plugin = NetherReactor::getInstance();

		$spireFileName = "structure" . DIRECTORY_SEPARATOR . "nether_reactor_spire.json";
		$patternFileName = "structure" . DIRECTORY_SEPARATOR . "pattern.json";
		$plugin->saveResource($spireFileName);
		$plugin->saveResource($patternFileName);
		$plugin->saveResource("structure" . DIRECTORY_SEPARATOR . "structure_config.yml");

		if (($spireContent = file_get_contents($plugin->getDataFolder() . $spireFileName)) === false ||
			!is_array($spirePositions = json_decode($spireContent, true, JSON_THROW_ON_ERROR))) {
			throw new AssumptionFailedError("Invalid pattern file");
		}
		foreach ($spirePositions as $pos) {
			$this->spireBlocks[] = $this->getVector3($pos);
		}

		if (($patternContent = file_get_contents($plugin->getDataFolder() . $patternFileName)) === false ||
			!is_array($patternLayers = json_decode($patternContent, true, JSON_THROW_ON_ERROR))) {
			throw new AssumptionFailedError("Invalid pattern file");
		}
		foreach ($patternLayers as $layer) {
			$layerBlocks = [];
			foreach ($layer["blocks"] as $posData) {
				$pos = $this->getVector3($posData);
				$layerBlocks[World::blockHash((int) $pos->x, (int) $pos->y, (int) $pos->z)] = $pos;
			}

			$transformations = [];

			$transformData = $layer["transformations"] ?? [];
			foreach ($transformData as $tData) {
				$transformations[] = new PatternLayerTransformation($this->getMaterial($tData["material"]), (int) $tData["tick"]);
			}

			$this->patternLayers[] = new PatternLayer(
				$this->getMaterial($layer["material"]),
				$layerBlocks,
				$transformations
			);
		}

		$config = new Config($plugin->getDataFolder() . "structure" . DIRECTORY_SEPARATOR . "structure_config.yml", Config::YAML);

		$this->maxY = $this->getInt($config->getNested("bounds.maxY", self::DEFAULT_MAX_BOUND_Y));
		$this->minY = $this->getInt($config->getNested("bounds.minY", self::DEFAULT_MIN_BOUND_Y));

		$spireMaterial = $config->getNested("nether_reactor_spire_material", "netherrack");
		if (!is_string($spireMaterial)) {
			throw new AssumptionFailedError("Nether reactor spire matherial should be a block name");
		}
		$this->spireMaterial = $this->getMaterial($spireMaterial);

		$platformsData = $config->get("platforms", []);
		if (!is_array($platformsData)) {
			throw new AssumptionFailedError("Invalid structure platforms data");
		}
		foreach ($platformsData as $pData) {
			$this->platforms[] = new Platform(
				$this->getVector3($pData["from"]),
				$this->getVector3($pData["to"]),
				$this->getMaterial($pData["material"])
			);
		}

		$roomX1 = $this->getInt($config->getNested("room.from.x"));
		$roomY1 = $this->getInt($config->getNested("room.from.y"));
		$roomZ1 = $this->getInt($config->getNested("room.from.z"));

		$roomX2 = $this->getInt($config->getNested("room.to.x"));
		$roomY2 = $this->getInt($config->getNested("room.to.y"));
		$roomZ2 = $this->getInt($config->getNested("room.to.z"));

		$this->minRoomPosition = new Vector3(min($roomX1, $roomX2), min($roomY1, $roomY2), min($roomZ1, $roomZ2));
		$this->maxRoomPosition = new Vector3(max($roomX1, $roomX2), max($roomY1, $roomY2), max($roomZ1, $roomZ2));
	}

	public function getVector3(array $data) : Vector3{
		return new Vector3(
			(int) ($data["x"] ?? throw new AssumptionFailedError("Expected \"x\" on position entry")),
			(int) ($data["y"] ?? throw new AssumptionFailedError("Expected \"y\" on position entry")),
			(int) ($data["z"] ?? throw new AssumptionFailedError("Expected \"z\" on position entry"))
		);
	}

	private function getMaterial(string $input) : Block{
		return StringToItemParser::getInstance()->parse($input)?->getBlock() ?? throw new AssumptionFailedError("Invalid structure config material input");
	}

	private function getInt(mixed $input) : int{
		if (!is_float($input) && !is_int($input)) {
			throw new AssumptionFailedError("Structire config input is not int");
		}

		return (int) $input;
	}

	public function getMinRoomPosition() : Vector3{
		return $this->minRoomPosition;
	}

	public function getMaxRoomPosition() : Vector3{
		return $this->maxRoomPosition;
	}

	public function getMaxBoundY() : int{
		return $this->maxY;
	}

	public function getMinBoundY() : int{
		return $this->minY;
	}

	/**
	 * @return PatternLayer[]
	 */
	public function getPatternLayers() : array{
		return $this->patternLayers;
	}

	public function isValidPattern(Position $corePosition) : bool{
		foreach ($this->patternLayers as $layer) {
			if (!$layer->isValid($corePosition)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @return Vector3[]
	 */
	public function getSpireBlocks() : array{
		return $this->spireBlocks;
	}

	public function getSpireMaterial() : Block{
		return $this->spireMaterial;
	}

	public function build(Position $corePosition) : void{
		$world = $corePosition->getWorld();

		// Generate spire
		foreach ($this->spireBlocks as $pos) {
			$world->setBlock($corePosition->addVector($pos), $this->spireMaterial);
		}

		// Generate platforms
		foreach ($this->platforms as $platform) {
			$platform->build($corePosition);
		}

		// Clear room
		$ignoredBlocks = [
			World::blockHash(0, 0, 0) => true //Core relative posision
		];
		foreach ($this->patternLayers as $layer) {
			$ignoredBlocks = $ignoredBlocks + $layer->getBlocks();
		}

		for ($relX = $this->minRoomPosition->x; $relX <= $this->maxRoomPosition->x; ++$relX) {
			/** @var int $relX */
			for ($relZ = $this->minRoomPosition->z; $relZ <= $this->maxRoomPosition->z; ++$relZ) {
				/** @var int $relZ */
				for ($relY = $this->minRoomPosition->y; $relY <= $this->maxRoomPosition->y; ++$relY) {
					/** @var int $relY */
					if (isset($ignoredBlocks[World::blockHash($relX, $relY, $relZ)])) {
						continue;
					}
					$world->setBlock($corePosition->add($relX, $relY, $relZ), VanillaBlocks::AIR());
				}
			}
		}
	}

	public function corruptSpire(Position $corePosition) : void{
		$blocksCount = count($this->spireBlocks);
		$amount = mt_rand(intdiv($blocksCount, 5), intdiv($blocksCount, 4));

		if ($amount > 1) {
			$world = $corePosition->getWorld();

			/** @var array <int, int> $$randomBlocks */
			$randomBlocks = array_rand($this->spireBlocks, $amount);
			foreach ($randomBlocks as $randomKey) {
				$world->setBlock($corePosition->addVector($this->spireBlocks[$randomKey]), VanillaBlocks::AIR());
			}
		}
	}
}
