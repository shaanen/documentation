<?php

require_once '/usr/share/php/random_compat/autoload.php';
require_once '/usr/share/php/Symfony/Component/Yaml/autoload.php';

use Symfony\Component\Yaml\Yaml;

$vupvca = bin2hex(random_bytes(16));
$vupvsa = bin2hex(random_bytes(16));
$vapvca = bin2hex(random_bytes(16));
$vapvsa = bin2hex(random_bytes(16));
$vsnvca = bin2hex(random_bytes(16));
$vsnvsa = bin2hex(random_bytes(16));

//$vupvca = 'XXX-vpn-user-portal/vpn-ca-api-XXX';
//$vupvsa = 'XXX-vpn-user-portal/vpn-server-api-XXX';
//$vapvca = 'XXX-vpn-admin-portal/vpn-ca-api-XXX';
//$vapvsa = 'XXX-vpn-admin-portal/vpn-server-api-XXX';
//$vsnvca = 'XXX-vpn-server-api/vpn-ca-api-XXX';
//$vsnvsa = 'XXX-vpn-server-api/vpn-server-api-XXX';

try {
    if (2 !== $argc) {
        throw new RuntimeException('please specify the hostname');
    }

    $hostName = $argv[1];

    $configFiles = [
        'vpn-user-portal' => [
            'config' => sprintf('/etc/vpn-user-portal/%s/config.yaml', $hostName),
            'apiProviders' => [
                'vpn-ca-api' => $vupvca,
                'vpn-server-api' => $vupvsa,
            ],
        ],
        'vpn-admin-portal' => [
            'config' => sprintf('/etc/vpn-admin-portal/%s/config.yaml', $hostName),
            'apiProviders' => [
                'vpn-ca-api' => $vapvca,
                'vpn-server-api' => $vapvsa,
            ],
        ],
        'vpn-server-api' => [
            'config' => sprintf('/etc/vpn-server-api/%s/config.yaml', $hostName),
            'apiConsumers' => [
                'vpn-user-portal' => $vupvsa,
                'vpn-admin-portal' => $vapvsa,
                'vpn-server-node' => $vsnvsa,
            ],
        ],
        'vpn-ca-api' => [
            'config' => sprintf('/etc/vpn-ca-api/%s/config.yaml', $hostName),
            'apiConsumers' => [
                'vpn-user-portal' => $vupvca,
                'vpn-admin-portal' => $vapvca,
                'vpn-server-node' => $vsnvca,
            ],
        ],
        'vpn-server-node' => [
            'config' => sprintf('/etc/vpn-server-node/%s/config.yaml', $hostName),
            'apiProviders' => [
                'vpn-ca-api' => $vsnvca,
                'vpn-server-api' => $vsnvsa,
            ],
        ],
    ];

    foreach ($configFiles as $tokenId => $c) {
        $configFile = $c['config'];

        // if we deploy a controller node, the vpn-server-node directory is
        // not available, but that is okay!
        if (@file_exists($configFile)) {
            $configData = Yaml::parse(file_get_contents($configFile));
            if (array_key_exists('apiProviders', $c)) {
                // apiProviders
                foreach ($c['apiProviders'] as $k => $v) {
                    $configData['apiProviders'][$k]['userPass'] = $v;
                }
            }
            if (array_key_exists('apiConsumers', $c)) {
                // apiConsumers
                foreach ($c['apiConsumers'] as $k => $v) {
                    $configData['apiConsumers'][$k] = $v;
                }
            }

            if (false === @file_put_contents($configFile, Yaml::dump($configData, 3))) {
                throw new RuntimeException(sprintf('unable to write "%s"', $configFile));
            }
        }
    }
} catch (Exception $e) {
    echo $e->getMessage().PHP_EOL;
    exit(1);
}
