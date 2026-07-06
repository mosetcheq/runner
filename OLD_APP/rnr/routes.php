<?php

/*

X3 ROUTER definice

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
RTR_ADDPARAMS		pripojeni nepouzitych vstupnich elementu k parametrum
RTR_ADDPARAMSARRAY	pripojeni nepouzitych vstupnich elementu k parametrum

*/

$routes = [

	// images
	[
		'route' => [
			[WebContent, RTR_TEXT],
			['galery', RTR_TEXT],
			[NULL, RTR_REQUIRED],
			[NULL, RTR_REQUIRED],
  ],
		'action' => ['images', '*'],
		'flags'  => RTR_NOCHECKSIZE | RTR_ADDPARAMS
 ],

    /*
	[
		'route' => [
			[WebContent, RTR_TEXT],
            [NULL, RTR_ALL],
		],
		'action' => ['images', 'thumb'],
		'flags'  => RTR_NOCHECKSIZE | RTR_ADDPARAMSARRAY,
	],
    */

    // Admin
	[
		'route' => [
			['admin', RTR_TEXT],
			[NULL, RTR_OPTIONAL],
            [NULL, RTR_OPTIONAL],
		],
		'action' => ['admin\*', '*'],
		'flags' => RTR_NOCHECKSIZE | RTR_ADDPARAMS
	],


    // zahrady
	[
		'route' => [
			['uroven', RTR_TEXT],
			[null, RTR_REQUIRED],
  ],
		'action' => ['Rnr','SetLevel'],
		'flags' => null,
 ],

	[
		'route' => [
			[null, RTR_REQUIRED],
			['osazovaci-plan', RTR_TEXT],
  ],
		'action' => ['zahrada','OsazovaciPlan'],
		'flags' => null,
 ],

	[
		'route' => [
			[null, RTR_REQUIRED],
			['video', RTR_TEXT],
  ],
		'action' => ['zahrada','Virtual'],
		'flags' => null,
 ],

	[
		'route' => [
			[null, RTR_REQUIRED],
			['virtualni-prohlidka', RTR_TEXT],
  ],
		'action' => ['zahrada','panorama'],
		'flags' => null,
 ],

	[
		'route' => [
			[null, RTR_REQUIRED],
			['detail-(\d+)', RTR_REGEXP],
  ],
		'action' => ['zahrada','Galerie'],
		'flags' => null,
 ],

	[
		'route' => [
			['atlas-rostlin', RTR_TEXT],
  ],
		'action' => ['zahrada','Atlas'],
		'flags' => RTR_NOCHECKSIZE | RTR_ADDPARAMS
 ],


    // sitemap
	[
		'route' => [
			['sitemap.xml', RTR_TEXT],
		],
		'action' => ['feeds', 'sitemap'],
		'flags' => null
	],


    // clanky
	[
		'route' => [
			['.*-a(\d+)\.html', RTR_REGEXP]
		],
		'action' => ['articles', 'show'],
		'flags' => RTR_NOCHECKSIZE
	],

    // stranky
	[
		'route' => [
			[null, RTR_SKIP]
		],
		'action' => ['documents','index'],
		'flags' => RTR_NOCHECKSIZE | RTR_ADDPARAMSARRAY,
	],
];