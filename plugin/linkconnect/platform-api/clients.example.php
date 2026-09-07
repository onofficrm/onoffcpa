<?php
/**
 * Example Platform CPS S2S machine clients.
 * Copy to clients.local.php — do NOT commit secrets.
 *
 * @return array{clients:list<array>,maxSkewSeconds?:int}
 */
return array(
    'maxSkewSeconds' => 300,
    'clients' => array(
        array(
            'name'   => 'traffic-icrm-prod',
            'keyId'  => 'platform_traffic_prod',
            'secret' => 'REPLACE_WITH_LONG_RANDOM_SECRET',
            'status' => 'active',
            'allowedSourceServices' => array('TRAFFIC', 'ONOFFCPA'),
            'scopes' => array(
                'platform.referral.bind',
                'platform.payment.report',
                'platform.core.ready',
            ),
        ),
        array(
            'name'   => 'backlink-prod',
            'keyId'  => 'platform_backlink_prod',
            'secret' => 'REPLACE_WITH_LONG_RANDOM_SECRET',
            'status' => 'active',
            'allowedSourceServices' => array('BACKLINK'),
            'scopes' => array(
                'platform.referral.bind',
                'platform.payment.report',
                'platform.core.ready',
            ),
        ),
        array(
            'name'   => 'seo-geo-prod',
            'keyId'  => 'platform_seo_geo_prod',
            'secret' => 'REPLACE_WITH_LONG_RANDOM_SECRET',
            'status' => 'active',
            'allowedSourceServices' => array('CONTENT'),
            'scopes' => array(
                'platform.referral.bind',
                'platform.payment.report',
                'platform.core.ready',
            ),
        ),
    ),
);
