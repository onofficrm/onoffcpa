<?php
/**
 * Example DOMAIN S2S machine clients.
 * Copy to clients.local.php — do NOT commit secrets.
 *
 * @return array{clients:list<array>,maxSkewSeconds?:int}
 */
return array(
    'maxSkewSeconds' => 300,
    'clients' => array(
        array(
            'name'   => 'catchdomain-prod',
            'keyId'  => 'domain_catchdomain_prod',
            'secret' => 'REPLACE_WITH_LONG_RANDOM_SECRET',
            'status' => 'active',
            'scopes' => array(
                'domain.referral.bind',
                'domain.payment.report',
                'domain.core.ready',
            ),
        ),
    ),
);
