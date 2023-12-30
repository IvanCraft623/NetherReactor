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

namespace IvanCraft623\NetherReactor\lang;

use pocketmine\lang\Translatable;

/**
 * This class contains factory methods for all the translations not implemented in PocketMine-MP used by this plugin
 * This class is generated manually, modify it by hand.
 *
 * @internal
 */
final class KnownTranslationFactory{
	public static function netherreactor_active() : Translatable{
		return new Translatable(KnownTranslationKeys::NETHERREACTOR_ACTIVE, []);
	}

	public static function netherreactor_build_too_high() : Translatable{
		return new Translatable(KnownTranslationKeys::NETHERREACTOR_BUILD_TOO_HIGH, []);
	}

	public static function netherreactor_build_too_low() : Translatable{
		return new Translatable(KnownTranslationKeys::NETHERREACTOR_BUILD_TOO_LOW, []);
	}

	public static function netherreactor_players_too_far() : Translatable{
		return new Translatable(KnownTranslationKeys::NETHERREACTOR_PLAYERS_TOO_FAR, []);
	}

	public static function netherreactor_wrong_pattern() : Translatable{
		return new Translatable(KnownTranslationKeys::NETHERREACTOR_WRONG_PATTERN, []);
	}
}
