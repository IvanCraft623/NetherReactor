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

namespace IvanCraft623\NetherReactor\entity;

use IvanCraft623\MobPlugin\entity\AgeableMob;
use IvanCraft623\MobPlugin\entity\ai\goal\FloatGoal;
use IvanCraft623\MobPlugin\entity\ai\goal\LookAtEntityGoal;
use IvanCraft623\MobPlugin\entity\ai\goal\MeleeAttackGoal;
use IvanCraft623\MobPlugin\entity\ai\goal\RandomLookAroundGoal;
use IvanCraft623\MobPlugin\entity\ai\goal\target\HurtByTargetGoal;
use IvanCraft623\MobPlugin\entity\ai\goal\WaterAvoidingRandomStrollGoal;
use IvanCraft623\MobPlugin\entity\MobType;
use IvanCraft623\MobPlugin\entity\monster\Monster;

use pocketmine\entity\Ageable;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\player\Player;
use function count;
use function mt_rand;

class Pigman extends Monster implements Ageable {

	private const TAG_IS_BABY = "IsBaby"; //TAG_Int

	protected bool $isBaby = false;

	private const NETWORK_TYPE_ID = "netherreactor:zombie_pigman";

	public static function getNetworkTypeId() : string{ return self::NETWORK_TYPE_ID; }

	protected function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo(1.9, 0.6, 1.71);
	}

	public function getName() : string{
		return "Zombie Pigman";
	}

	public function getMobType() : MobType{
		return MobType::UNDEAD();
	}

	public function isBaby() : bool{
		return $this->isBaby;
	}

	public function setBaby(bool $value = true) : void{
		if ($value !== $this->isBaby) {
			$this->setScale($value ? $this->getBabyScale() : 1);

			$this->isBaby = $value;
			$this->networkPropertiesDirty = true;
		}
	}

	public function getBabyScale() : float{
		return 0.5;
	}

	public function isFireProof() : bool{
		return true;
	}

	public function getDefaultMovementSpeed() : float{
		return 0.23;
	}

	protected function registerGoals() : void{
		$this->goalSelector->addGoal(1, new FloatGoal($this));
		$this->goalSelector->addGoal(2, new MeleeAttackGoal($this, 1, false));
		$this->goalSelector->addGoal(3, new WaterAvoidingRandomStrollGoal($this, 1));
		$this->goalSelector->addGoal(7, new LookAtEntityGoal($this, Player::class, 8));
		$this->goalSelector->addGoal(8, new RandomLookAroundGoal($this));

		$this->targetSelector->addGoal(1, (new HurtByTargetGoal($this))->setAlertOthers());
	}

	protected function initProperties() : void{
		parent::initProperties();

		$this->setMaxHealth(20);
		$this->setAttackDamage(5);
	}

	protected function initEntity(CompoundTag $nbt) : void{
		parent::initEntity($nbt);

		$isBabyByte = $nbt->getByte(self::TAG_IS_BABY, -1);
		if ($isBabyByte === 1 || ($isBabyByte === -1 && AgeableMob::getRandomStartAge() !== AgeableMob::ADULT_AGE)) {
			$this->setBaby();
		}
	}

	public function saveNBT() : CompoundTag{
		$nbt = parent::saveNBT();

		$nbt->setByte(self::TAG_IS_BABY, $this->isBaby ? 1 : 0);

		return $nbt;
	}

	protected function syncNetworkData(EntityMetadataCollection $properties) : void{
		parent::syncNetworkData($properties);
		$properties->setGenericFlag(EntityMetadataFlags::BABY, $this->isBaby);
	}

	public function getXpDropAmount() : int{
		if ($this->hasBeenDamagedByPlayer()) {
			return ($this->isBaby ? 12 : 5) + (count($this->armorInventory->getContents()) * mt_rand(1, 3));
		}

		return 0;
	}

	public function generateEquipment() : void{
		$this->inventory->setItemInHand(VanillaItems::GOLDEN_SWORD()); //TODO: random enchantments
	}

	public function getDrops() : array {
		$drops = $this->getEquipmentDrops();

		//TODO: looting enchantment probability increase :P
		$drops[] = VanillaItems::ROTTEN_FLESH()->setCount(mt_rand(0, 1));
		$drops[] = VanillaItems::GOLD_NUGGET()->setCount(mt_rand(0, 1));

		//TODO: Each looting enchantment level should increse by 10(0.01%) this value
		$dropGoldIngotChance = 25; // 0.025%
		if (mt_rand(0, 1000) <= $dropGoldIngotChance) {
			$drops[] = VanillaItems::GOLD_INGOT();
		}

		return $drops;
	}
}
