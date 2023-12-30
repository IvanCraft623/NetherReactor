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

namespace IvanCraft623\NetherReactor\block;

use customiesdevs\customies\block\permutations\BlockProperty;
use customiesdevs\customies\block\permutations\Permutable;
use customiesdevs\customies\block\permutations\Permutation;
use IvanCraft623\NetherReactor\block\tile\NetherReactor as NRTile;

use IvanCraft623\NetherReactor\lang\KnownTranslationFactory;
use IvanCraft623\NetherReactor\NetherReactor as Plugin;
use IvanCraft623\NetherReactor\structure\NetherReactorStructure;

use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeInfo;
use pocketmine\block\NetherReactor as PMNetherReactor;
use pocketmine\data\bedrock\block\convert\BlockStateReader;
use pocketmine\data\bedrock\block\convert\BlockStateWriter;
use pocketmine\data\runtime\InvalidSerializedRuntimeDataException;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\data\runtime\RuntimeDataReader;
use pocketmine\data\runtime\RuntimeDataSizeCalculator;
use pocketmine\data\runtime\RuntimeDataWriter;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;
use function array_map;
use function get_class;

class NetherReactor extends PMNetherReactor implements Permutable {

	private const NETHERREACTOR_STATE_NAME = "ivancraft623:netherreactor_state";

	protected NetherReactorType $type;

	public function __construct(BlockIdentifier $idInfo, string $name, BlockTypeInfo $typeInfo){
		$this->type = NetherReactorType::INACTIVE();

		parent::__construct($idInfo, $name, $typeInfo);
	}

	public function describeBlockItemState(RuntimeDataDescriber $describer) : void{
		if ($describer instanceof RuntimeDataReader) {
			$this->type = match($describer->readInt(2)){
				0 => NetherReactorType::INACTIVE(),
				1 => NetherReactorType::ACTIVE(),
				2 => NetherReactorType::USED(),
				default => throw new InvalidSerializedRuntimeDataException("Invalid serialized value for NetherReactorType")
			};
		} elseif ($describer instanceof RuntimeDataWriter) {
			$describer->writeInt(2, match($this->type){
				NetherReactorType::INACTIVE() => 0,
				NetherReactorType::ACTIVE() => 1,
				NetherReactorType::USED() => 2,
				default => throw new AssumptionFailedError("All NetherReactorType cases should be covered")
			});
		} else {
			$unused = 0;
			$describer->int(2, $unused);

			if (!$describer instanceof RuntimeDataSizeCalculator) {
				Plugin::getInstance()->getLogger()->warning("Unhandled runtime data describer: " . get_class($describer));
			}
		}
	}

	public function getType() : NetherReactorType{
		return $this->type;
	}

	/** @return $this */
	public function setType(NetherReactorType $type) : self{
		$this->type = $type;

		return $this;
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		return [$this->asItem()];
	}

	public function getBlockProperties() : array{
		return [
			new BlockProperty(self::NETHERREACTOR_STATE_NAME, array_map(function(NetherReactorType $type) : string {
				return $type->name();
			}, NetherReactorType::getAll())),
		];
	}

	public function getPermutations() : array{
		$permutations = [];
		foreach (NetherReactorType::getAll() as $state) {
			$permutations[] = (
				new Permutation("query.block_property('" . self::NETHERREACTOR_STATE_NAME . "') == '" . $state->name() . "'")
			)
				->withComponent("minecraft:material_instances", CompoundTag::create()
					->setTag("mappings", CompoundTag::create())
					->setTag("materials", CompoundTag::create()
						->setTag("*", CompoundTag::create()
							->setByte("ambient_occlusion", 1)
							->setByte("face_dimming", 1)
							->setString("render_method", "opaque")
							->setString("texture", "nether_reactor_" . $state->name())
						)
					)
				)
				->withComponent("minecraft:unit_cube", CompoundTag::create());
		}

		return $permutations;
	}

	public function getCurrentBlockProperties() : array {
		return [$this->type->name()];
	}

	public function serializeState(BlockStateWriter $blockStateOut) : void {
		$blockStateOut->writeString(self::NETHERREACTOR_STATE_NAME, $this->type->name());
	}

	public function deserializeState(BlockStateReader $blockStateIn) : void {
		$this->type = match($blockStateIn->readString(self::NETHERREACTOR_STATE_NAME)){
			"active" => NetherReactorType::ACTIVE(),
			"used" => NetherReactorType::USED(),
			default => NetherReactorType::INACTIVE()
		};
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if (!$this->type->equals(NetherReactorType::INACTIVE())) {
			//TODO: allow toggling this check off, in vanilla an inifinite nether reactor was able to be made
			//because they didn't check the reactor core state for reactor activation. :P
			return false;
		}
		if ($player === null || !$player->hasFiniteResources()) {
			return false;
		}

		$structure = NetherReactorStructure::getInstance();
		if (!$structure->isValidPattern($this->position)) {
			$player->sendMessage(KnownTranslationFactory::netherreactor_wrong_pattern()->prefix(TextFormat::GRAY));
			return false;
		}

		$world = $this->position->getWorld();
		if ($this->position->y + $structure->getMaxBoundY() >= $world->getMaxY()) {
			$player->sendMessage(KnownTranslationFactory::netherreactor_build_too_high()->prefix(TextFormat::GRAY));
			return false;
		}
		if ($this->position->y + $structure->getMinBoundY() < $world->getMinY()) {
			$player->sendMessage(KnownTranslationFactory::netherreactor_build_too_low()->prefix(TextFormat::GRAY));
			return false;
		}

		//TODO: player position check

		//Only if the nether reactor is inactive all the process is done.
		if (($tile = $world->getTile($this->position)) instanceof NRTile) {
			$tile->initialize();
		}

		NetherReactorStructure::getInstance()->build($this->position);
		$world->setBlock($this->position, $this->setType(NetherReactorType::ACTIVE()));
		$player->sendMessage(KnownTranslationFactory::netherreactor_active()->prefix(TextFormat::YELLOW));

		return true;
	}

	public function onScheduledUpdate() : void{
		$world = $this->position->getWorld();
		$netherreactor = $world->getTile($this->position);
		if($netherreactor instanceof NRTile && $netherreactor->onUpdate()){
			$world->scheduleDelayedBlockUpdate($this->position, 1);
		}
	}
}
