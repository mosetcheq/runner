<?php

/*

Runner ROUTER definice

Typy elementu Route:
RTR_TEXT        porovnani textu
RTR_REQUIRED    pozadovany parametr
RTR_REGEXP      regulerni vyraz, parametr = vysledek hledani
RTR_OPTIONAL    nepovinny parametr
RTR_ALL         vsechno ostatni vcetne stavajiciho elementu do parametru
RTR_SKIP        preskocit element

Flagy
RTR_CONTINUE		pokracovat v prohledavani pokud je shoda
RTR_NOCHECKSIZE		nekontrolovat pocet elementu pozadavku a definice
RTR_REVERSE		prevraceni vstupnich elementu
RTR_ADDPARAMS		pripojeni nepouzitych vstupnich elementu k parametrum (x parametru fce)
RTR_ADDPARAMSARRAY	pripojeni nepouzitych vstupnich elementu k parametrum jako pole (1 parametr fce)
RTR_API_MODE		nazvoslovi metod v modu pro API - on<http_method>method_name

*/

$routes = [

	[
		'route' => [
			[null, RTR_OPTIONAL],
			[null, RTR_OPTIONAL]
		],
		'action' => ['*', '*'],
		'flags' => RTR_NOCHECKSIZE | RTR_ADDPARAMS,
	],
];