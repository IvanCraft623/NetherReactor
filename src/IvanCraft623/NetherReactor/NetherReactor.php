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

namespace IvanCraft623\NetherReactor;

use IvanCraft623\NetherReactor\block\ExtraBlockRegisterHelper;
use IvanCraft623\NetherReactor\entity\ExtraEntityRegisterHelper;
use IvanCraft623\NetherReactor\structure\NetherReactorStructure;

use libCustomPack\libCustomPack;

use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ResourcePack;
use pocketmine\utils\SingletonTrait;
use Symfony\Component\Filesystem\Path;
use function unlink;

class NetherReactor extends PluginBase {
	use SingletonTrait;

	private static ResourcePack $pack;

	public function onLoad() : void {
		self::setInstance($this);

		//Build and register resource pack
		libCustomPack::registerResourcePack(self::$pack = libCustomPack::generatePackFromResources($this));
		$this->getLogger()->debug('Resource pack installed');
	}

	public function onEnable() : void {
		ExtraBlockRegisterHelper::init();
		ExtraEntityRegisterHelper::init();
		NetherReactorStructure::getInstance();
	}

	public function onDisable() : void{
		libCustomPack::unregisterResourcePack(self::$pack);
		$this->getLogger()->debug('Resource pack uninstalled');

		unlink(Path::join($this->getDataFolder(), self::$pack->getPackName() . '.mcpack'));
		$this->getLogger()->debug('Resource pack file deleted');
	}
}
