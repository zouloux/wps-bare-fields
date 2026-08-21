<?php

namespace BareFields\helpers;

final class PreviewHelper {

	public const string ENV_KEY = "WPS_PREVIEW_SECRET";
	public const string QUERY_KEY = "preview";
	public const int SIGNATURE_LENGTH = 16;

	public static function getSecret (): ?string {
		$secret = $_ENV[self::ENV_KEY] ?? getenv(self::ENV_KEY);
		return is_string($secret) && $secret !== "" ? $secret : null;
	}

	public static function createToken ( int $postID, ?string $secret = null ): ?string {
		$secret ??= self::getSecret();
		if ( is_null($secret) )
			return null;
		$signature = substr(hash_hmac("sha256", (string) $postID, $secret), 0, self::SIGNATURE_LENGTH);
		return "$postID.$signature";
	}

	public static function getPostID ( mixed $token, ?string $secret = null ): int|false {
		$pattern = '/^([1-9][0-9]*)\.([a-f0-9]{'.self::SIGNATURE_LENGTH.'})$/';
		if ( !is_string($token) || preg_match($pattern, $token, $matches) !== 1 )
			return false;
		$secret ??= self::getSecret();
		if ( is_null($secret) )
			return false;
		$postID = $matches[1];
		$expectedSignature = substr(hash_hmac("sha256", $postID, $secret), 0, self::SIGNATURE_LENGTH);
		return hash_equals($expectedSignature, $matches[2]) ? (int) $postID : false;
	}
}
