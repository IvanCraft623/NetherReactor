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
use pocketmine\math\Vector3;
use pocketmine\world\format\Chunk;
use pocketmine\world\Position;
use function max;
use function min;

final class Platform{

	public function __construct(
		private Vector3 $from,
		private Vector3 $to,
		private Block $material
	) {
	}

	public function build(Position $corePosition) : void{
		$world = $corePosition->getWorld();

		$minX = min($this->from->x, $this->to->x);
		$minY = max($world->getMinY(), min($this->from->y, $this->to->y));
		$minZ = min($this->from->z, $this->to->z);

		$maxX = max($this->from->x, $this->to->x);
		$maxY = min($world->getMaxY(), max($this->from->y, $this->to->y));
		$maxZ = max($this->from->z, $this->to->z);

		for ($x = $minX; $x <= $maxX; ++$x) {
			for ($z = $minZ; $z <= $maxZ; ++$z) {
				$world->loadChunk($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE);
				for ($y = $minY; $y <= $maxY; ++$y) {
					$world->setBlock($corePosition->add($x, $y, $z), $this->material);
				}
			}
		}
	}

}
