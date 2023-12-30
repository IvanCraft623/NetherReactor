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

use pocketmine\block\BlockTypeIds;

use function count;
use function mb_strtoupper;
use function preg_match;

/**
 * Every block in {@link ExtraVanillaBlocks} has a corresponding constant in this class. These constants can be used to
 * identify and compare block types efficiently using {@link Block::getTypeId()}.
 *
 * @method static int NETHER_REACTOR_CORE()
 */
final class ExtraBlockTypeIds{
	/**
	 * @var int[]
	 * @phpstan-var array<string, int>
	 */
	private static $members = null;

	protected static function setup() : void {
		self::register("nether_reactor_core");
	}

	private static function verifyName(string $name) : void{
		if(preg_match('/^(?!\d)[A-Za-z\d_]+$/u', $name) === 0){
			throw new \InvalidArgumentException("Invalid member name \"$name\", should only contain letters, numbers and underscores, and must not start with a number");
		}
	}

	/**
	 * Adds the given typeId to the registry.
	 *
	 * @throws \InvalidArgumentException
	 */
	private static function register(string $name) : void{
		self::verifyName($name);
		$upperName = mb_strtoupper($name);
		if(isset(self::$members[$upperName])){
			throw new \InvalidArgumentException("\"$upperName\" is already reserved");
		}
		self::$members[$upperName] = BlockTypeIds::newId();
	}

	/**
	 * @internal Lazy-inits the enum if necessary.
	 *
	 * @throws \InvalidArgumentException
	 */
	protected static function checkInit() : void{
		if(self::$members === null){
			self::$members = [];
			self::setup();
		}
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	private static function _registryFromString(string $name) : int{
		self::checkInit();
		$upperName = mb_strtoupper($name);
		if(!isset(self::$members[$upperName])){
			throw new \InvalidArgumentException("No such registry member: " . self::class . "::" . $upperName);
		}
		return self::$members[$upperName];
	}

	/**
	 * @param string  $name
	 * @param mixed[] $arguments
	 * @phpstan-param list<mixed> $arguments
	 *
	 * @return int
	 */
	public static function __callStatic($name, $arguments){
		if(count($arguments) > 0){
			throw new \ArgumentCountError("Expected exactly 0 arguments, " . count($arguments) . " passed");
		}
		try{
			return self::_registryFromString($name);
		}catch(\InvalidArgumentException $e){
			throw new \Error($e->getMessage(), 0, $e);
		}
	}
}
