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

use IvanCraft623\NetherReactor\entity\Pigman;
use IvanCraft623\NetherReactor\NetherReactor as Main;

use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Location;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;

use function cos;
use function deg2rad;
use function lcg_value;
use function min;
use function mt_rand;
use function sin;

/**
 * TODO: Maybe make this customizable?
 */
final class NetherReactorRound{

	public const LOOT_DESPAWN_DELAY = 600; //30 segs

	public const MAX_PIGMEN_SPAWN_PER_ROUND = 2;
	public const MAX_PIGMEN_COUNT = 3;

	public static function getRandomLoot() : Item{
		$probability = mt_rand(1, 50);
		if ($probability <= 24) { //48% probability
			return match(mt_rand(1, 6)){
				1 => VanillaBlocks::CACTUS()->asItem(),
				2 => VanillaBlocks::BROWN_MUSHROOM()->asItem(),
				3 => VanillaBlocks::RED_MUSHROOM()->asItem(),
				4 => VanillaBlocks::SUGARCANE()->asItem(),
				5 => VanillaItems::MELON_SEEDS(),
				6 => VanillaItems::PUMPKIN_SEEDS()
			};
		} elseif ($probability <= 35) { //22% probability
			return VanillaItems::GLOWSTONE_DUST();
		}

		//30% probability
		return VanillaItems::NETHER_QUARTZ();
	}

	public function __construct(
		private int $tick,
		private int $minLootAmount,
		private int $maxLootAmount,
		private bool $spawnPigmen = false
	) {
		if ($tick < 1) {
			throw new \InvalidArgumentException("Round tick cannot be less than 1");
		}
		if ($minLootAmount > $maxLootAmount) {
			throw new \InvalidArgumentException("Min loot amount is greater than max loot amound");
		}
	}

	public function canStart(int $currentTick) : bool{
		return $this->tick === $currentTick;
	}

	public function start(Position $position, AxisAlignedBB $roomBB) : void{
		$world = $position->getWorld();
		$itemSpawnAmount = mt_rand($this->minLootAmount, $this->maxLootAmount);
		for ($i = 0; $i < $itemSpawnAmount; $i++) {
			$randomDegree = deg2rad(mt_rand(1, 360));
			$randomDistance = mt_rand(3, 8);

			$x = $position->x + ($randomDistance * cos($randomDegree));
			$z = $position->z + ($randomDistance * sin($randomDegree));

			if (($itemEntity = $world->dropItem(new Vector3($x + 0.5, $position->y - 1, $z + 0.5), self::getRandomLoot())) !== null) {
				$itemEntity->setDespawnDelay(self::LOOT_DESPAWN_DELAY);
			}
		}

		if ($this->spawnPigmen) {
			$this->tryToSpawnPigmen($position, $roomBB);
		}
	}

	public function tryToSpawnPigmen(Position $position, AxisAlignedBB $roomBB) : void{
		if (!Main::isMobPluginDetected()) {
			return;
		}

		$world = $position->getWorld();
		if ($world->getDifficulty() === World::DIFFICULTY_PEACEFUL) {
			return;
		}

		$pigmenCount = 0;
		foreach ($world->getNearbyEntities($roomBB) as $entity) {
			if ($entity instanceof Pigman && ++$pigmenCount >= self::MAX_PIGMEN_COUNT) {
				return;
			}
		}

		$pigmenToSpawn = min(self::MAX_PIGMEN_SPAWN_PER_ROUND, self::MAX_PIGMEN_COUNT - $pigmenCount);

		$layers = NetherReactorStructure::getInstance()->getPatternLayers();
		for ($i = 0; $i < $pigmenToSpawn; $i++) {
			while (true) {
				$x = mt_rand((int) $roomBB->minX, (int) $roomBB->maxX);
				$z = mt_rand((int) $roomBB->minZ, (int) $roomBB->maxZ);

				if ($x === (int) $position->x && $z === (int) $position->z) {
					continue;
				}

				foreach ($layers as $layer) {
					foreach ($layer->getBlocks() as $pos) {
						if ((int) $pos->x === $x || (int) $pos->z === $z) {
							continue 3;
						}
					}
				}

				$entity = new Pigman(new Location($x + 0.5, $roomBB->minY, $z + 0.5, $world, lcg_value() * 360, 0));
				$entity->spawnToAll();

				break;
			}
		}
	}
}
