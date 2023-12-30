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

use customiesdevs\customies\block\CustomiesBlockFactory;

use customiesdevs\customies\item\CreativeInventoryInfo;
use IvanCraft623\NetherReactor\block\tile\NetherReactor as TileNetherReactor;

use pocketmine\block\tile\TileFactory;
use pocketmine\crafting\ExactRecipeIngredient;
use pocketmine\crafting\ShapedRecipe;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\Server;

final class ExtraBlockRegisterHelper{

	public static function init() : void{
		self::registerBlocks();
		self::registerTiles();
		self::registerStringToItemParserNames();
		self::registerCraftingRecipes();
	}

	private static function registerBlocks() : void{
		$blockFactory = CustomiesBlockFactory::getInstance();

		$blockFactory->registerBlock(static fn() => ExtraVanillaBlocks::NETHER_REACTOR_CORE(), "ivancraft623:nether_reactor_core", creativeInfo: new CreativeInventoryInfo(CreativeInventoryInfo::CATEGORY_ITEMS));
	}

	private static function registerTiles() : void{
		$tileFactory = TileFactory::getInstance();

		$tileFactory->register(TileNetherReactor::class, ["NetherReactor", "minecraft:netherreactor"]);
	}

	private static function registerStringToItemParserNames() : void{
		$parser = StringToItemParser::getInstance();

		$parser->registerBlock("inactive_netherreactor", fn() => ExtraVanillaBlocks::NETHER_REACTOR_CORE());
		$parser->registerBlock("active_netherreactor", fn() => ExtraVanillaBlocks::NETHER_REACTOR_CORE()->setType(NetherReactorType::ACTIVE()));
		$parser->registerBlock("used_netherreactor", fn() => ExtraVanillaBlocks::NETHER_REACTOR_CORE()->setType(NetherReactorType::USED()));
	}

	private static function registerCraftingRecipes() : void{
		Server::getInstance()->getCraftingManager()->registerShapedRecipe(new ShapedRecipe(
			[
				"ABA",
				"ABA",
				"ABA"
			],
			[
				"A" => new ExactRecipeIngredient(VanillaItems::IRON_INGOT()),
				"B" => new ExactRecipeIngredient(VanillaItems::DIAMOND())
			],
			[ExtraVanillaBlocks::NETHER_REACTOR_CORE()->asItem()]
		));
	}
}
