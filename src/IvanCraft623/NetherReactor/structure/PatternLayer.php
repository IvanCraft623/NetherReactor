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

use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;

/**
 * @phpstan-import-type BlockPosHash from World
 */
final class PatternLayer{

	private Item $materialItem;

	/**
	 * @param Vector3[]                    $blocks
	 * @param PatternLayerTransformation[] $transformations
	 *
	 * @phpstan-param array<BlockPosHash, Vector3> $blocks
	 */
	public function __construct(
		private Block $material,
		private array $blocks,
		private array $transformations = []
	) {
		$this->materialItem = $material->asItem();
	}

	public function getMaterial() : Block{
		return $this->material;
	}

	/**
	 * @return Vector3[]
	 * @phpstan-return array<BlockPosHash, Vector3>
	 */
	public function getBlocks() : array{
		return $this->blocks;
	}

	/**
	 * @return PatternLayerTransformation[]
	 */
	public function getTransformations() : array{
		return $this->transformations;
	}

	public function isValid(Position $corePosition) : bool{
		$world = $corePosition->getWorld();
		foreach ($this->blocks as $pos) {
			//TODO: this is a hack for only compare item state
			if (!$world->getBlock($corePosition->addVector($pos))->asItem()->equalsExact($this->materialItem)) {
				return false;
			}
		}

		return true;
	}

	public function transform(Position $corePosition, PatternLayerTransformation $transformation) : void{
		$world = $corePosition->getWorld();
		foreach ($this->blocks as $pos) {
			$world->setBlock($corePosition->addVector($pos), $transformation->getMaterial());
		}
	}
}
