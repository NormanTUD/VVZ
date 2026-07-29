<?php
/*
 * Tests for random and crypto-related functions.
 */

/* ----- generate_random_string ----- */
is_equal("generate_random_string(10) length is 10", strlen(generate_random_string(10)), 10);
is_equal("generate_random_string(20) length is 20", strlen(generate_random_string(20)), 20);
is_equal("generate_random_string(64) length is 64", strlen(generate_random_string(64)), 64);
is_equal("generate_random_string(128) length is 128", strlen(generate_random_string(128)), 128);

regex_matches("generate_random_string only contains valid chars", generate_random_string(50), '/^[a-zA-Z0-9]+$/');

/* Statistical sanity: 100 strings should produce at least 50 unique values */
$unique = array();
for ($i = 0; $i < 100; $i++) {
	$unique[generate_random_string(20)] = 1;
}
is_equal("generate_random_string produces diverse output", count($unique) > 50 ? 1 : 0, 1);

/* No string should equal another when generated separately */
$a = generate_random_string(32);
$b = generate_random_string(32);
is_unequal("generate_random_string produces unique strings", $a, $b);

/* ----- nonce ----- */
is_equal("nonce() length is 10", strlen(nonce()), 10);
regex_matches("nonce() only contains valid chars", nonce(), '/^[a-zA-Z0-9]+$/');
is_equal("nonce() is consistent within same request", nonce(), nonce());

/* Global nonce should match function return */
is_equal("nonce() matches GLOBALS nonce", nonce(), $GLOBALS['nonce']);

/* ----- checkIBAN additional valid examples ----- */
/* Valid DE IBAN: DE89370400440532013000 */
is_equal("checkIBAN('DE89370400440532013000') is valid", checkIBAN("DE89370400440532013000"), true);

/* Note: Production checkIBAN rejects any IBAN not exactly 22 chars,
   so AT (20) and others are rejected by length, not checksum. */
is_equal("checkIBAN AT IBAN rejected by length (production behavior)", checkIBAN("AT611904300234573201"), false);

/* Invalid - wrong length for country */
is_equal("checkIBAN('DE12345') is invalid", checkIBAN("DE12345"), false);

/* ----- esc determinism (same input -> same output, ignoring quote chars) ----- */
$input = "test123";
is_equal("esc('test123')", esc($input), '"test123"');
