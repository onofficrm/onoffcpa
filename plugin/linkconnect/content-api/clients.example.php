<?php
/**
 * Example Content S2S machine clients.
 *
 * Copy to clients.local.php and fill real values locally.
 * Do NOT commit clients.local.php or production secrets.
 *
 * Production: configure via clients.local.php on the server (outside git)
 * or LC_CONTENT_S2S_CLIENTS_JSON env (ops-managed). This repo ships no prod secrets.
 *
 * @return array{clients:list<array>,maxSkewSeconds?:int}
 */
return array(
    'maxSkewSeconds' => 300,
    'clients' => array(
        array(
            'name'   => 'content-bff-dev',
            'keyId'  => 'cnt_dev_example_key',
            'secret' => 'REPLACE_WITH_LONG_RANDOM_SECRET',
            // Partner ownership is bound here — never trust request pt_id.
            'ptId'   => 0, // set to a real active partner id in local only
            // 'ptCode' => 'PTN-0001',
            'status' => 'active',
            'scopes' => array(
                'content.campaigns.read',
                'content.links.read',
                'content.links.create',
                'content.analytics.read',
            ),
        ),
    ),
);
