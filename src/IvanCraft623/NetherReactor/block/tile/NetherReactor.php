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

namespace IvanCraft623\NetherReactor\block\tile;

use IvanCraft623\NetherReactor\block\ExtraVanillaBlocks;
use IvanCraft623\NetherReactor\block\NetherReactorType;
use IvanCraft623\NetherReactor\structure\NetherReactorStructure;

use pocketmine\block\tile\Tile;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\Binary;

class NetherReactor extends Tile{

	private const TAG_INITIALIZED = "IsInitialized"; //TAG_Byte
	private const TAG_FINISHED = "HasFinished"; //TAG_Byte
	private const TAG_PROGRESS = "Progress"; //TAG_Short

	public const TIME_NIGHT = 15000;
	public const TIME_STEP_PER_TICK = 25;

	public const NETHER_REACTOR_DURATION = 920; //ticks

	protected bool $initialized = false;
	protected bool $finished = false;
	protected bool $turnedNight = true;

	protected int $progress = 0;

	protected AxisAlignedBB $roomBB;

	public function isInitialized() : bool{
		return $this->initialized;
	}

	/** @return $this */
	public function setInitialized(bool $initialized) : self{
		$this->initialized = $initialized;

		return $this;
	}

	public function isFinished() : bool{
		return $this->finished;
	}

	/** @return $this */
	public function setFinished(bool $finished) : self{
		$this->finished = $finished;

		return $this;
	}

	public function isRunning() : bool{
		return $this->initialized && !$this->finished;
	}

	public function getRoomBoundingBox() : AxisAlignedBB{
		if (!isset($this->roomBB)) {
			$structure = NetherReactorStructure::getInstance();
			$min = $structure->getMinRoomPosition();
			$max = $structure->getMaxRoomPosition();
			$this->roomBB = new AxisAlignedBB(
				$min->x + $this->position->x,
				$min->y + $this->position->y,
				$min->z + $this->position->z,
				$max->x + $this->position->x,
				$max->y + $this->position->y,
				$max->z + $this->position->z
			);
		}
		return $this->roomBB;
	}

	public function readSaveData(CompoundTag $nbt) : void{
		$this->initialized = $nbt->getByte(self::TAG_INITIALIZED, 0) !== 0;
		$this->finished = $nbt->getByte(self::TAG_FINISHED, 0) !== 0;
		$this->progress = $nbt->getShort(self::TAG_PROGRESS, 0);

		if ($this->isRunning()) {
			$this->position->getWorld()->scheduleDelayedBlockUpdate($this->position, 1);
		}
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$nbt->setByte(self::TAG_INITIALIZED, $this->initialized ? 1 : 0);
		$nbt->setByte(self::TAG_FINISHED, $this->finished ? 1 : 0);
		$nbt->getShort(self::TAG_PROGRESS, Binary::signShort($this->progress));
	}

	public function initialize() : void{
		$this->initialized = true;
		$this->turnedNight = false;

		$this->position->getWorld()->scheduleDelayedBlockUpdate($this->position, 1);
	}

	public function finish() : void{
		$this->finished = true;

		$this->position->getWorld()->setBlock($this->position, ExtraVanillaBlocks::NETHER_REACTOR_CORE()->setType(NetherReactorType::USED()));
		NetherReactorStructure::getInstance()->corruptSpire($this->position);
	}

	public function onUpdate() : bool{
		$hasUpdate = false;
		if ($this->isRunning()) {
			//Advance time until is night
			if (!$this->turnedNight) {
				$world = $this->position->getWorld();
				$time = $world->getTimeOfDay();

				$timeAddition = self::TIME_STEP_PER_TICK;
				if ($time < self::TIME_NIGHT && ($diff = (self::TIME_NIGHT - $time)) < $timeAddition) {
					$timeAddition = $diff;
					$this->turnedNight = true;
				}

				$world->setTime($world->getTime() + $timeAddition);
			}

			$structure = NetherReactorStructure::getInstance();

			foreach ($structure->getPatternLayers() as $layer) {
				foreach ($layer->getTransformations() as $transform) {
					if ($transform->getTick() === $this->progress) {
						$layer->transform($this->position, $transform);
					}
				}
			}

			foreach ($structure->getRounds() as $round) {
				if ($round->canStart($this->progress)) {
					$round->start($this->getPosition(), $this->getRoomBoundingBox());
				}
			}

			if ($this->progress >= self::NETHER_REACTOR_DURATION) {
				$this->finish();
			}

			$this->progress++;
			$hasUpdate = true;
		}
		return $hasUpdate;
	}
}
