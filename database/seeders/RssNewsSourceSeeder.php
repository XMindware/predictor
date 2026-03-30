<?php

namespace Database\Seeders;

use App\Models\Provider;
use App\Models\RssNewsSource;
use Illuminate\Database\Seeder;

/**
 * Seeds the rss_news_sources table with one row per feed URL.
 *
 * iata = null  → global default feeds (fetched for every watch target)
 * iata = 'XYZ' → city-specific feeds for that airport
 *
 * All entries use updateOrCreate on (provider_id, url) so re-running is safe.
 */
class RssNewsSourceSeeder extends Seeder
{
    public function run(): void
    {
        $provider = Provider::updateOrCreate(
            ['slug' => 'rss-news'],
            [
                'name'    => 'RSS City Feeds',
                'service' => 'news',
                'driver'  => 'rss',
                'active'  => true,
                'notes'   => 'City-targeted RSS/Atom feeds loaded from rss_news_sources table. No API key required.',
            ],
        );

        $pid = $provider->id;

        // Keep provider configs for operational settings (timeouts, limits, keywords)
        // but the feed_map JSON blob is no longer used — feeds live in this table.
        foreach ($this->providerConfigs() as $key => $value) {
            $provider->configs()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $sources = $this->sources();
        $seeded  = 0;

        foreach ($sources as $row) {
            RssNewsSource::updateOrCreate(
                ['provider_id' => $pid, 'url' => $row['url']],
                array_merge($row, ['provider_id' => $pid]),
            );
            $seeded++;
        }

        // Remove the old feed_map JSON blob — no longer needed
        $provider->configs()->where('key', 'feed_map')->delete();

        $cities  = collect($sources)->whereNotNull('iata')->pluck('iata')->unique()->count();
        $globals = collect($sources)->whereNull('iata')->count();

        $this->command?->info(sprintf(
            '[RssNewsSourceSeeder] %d sources seeded (%d global defaults + %d city-specific across %d airports).',
            $seeded, $globals, $seeded - $globals, $cities,
        ));
    }

    // ─── Provider operational config ─────────────────────────────────────────

    /** @return array<string, string> */
    private function providerConfigs(): array
    {
        return [
            'timeout_seconds'       => '12',
            'max_articles_per_feed' => '20',
            'min_relevance_hits'    => '1',
            'relevance_keywords'    => implode(',', [
                // English
                'airport', 'flight', 'airline', 'delay', 'cancel', 'diverted',
                'diversion', 'disruption', 'closure', 'evacuation',
                'storm', 'hurricane', 'cyclone', 'tornado', 'flood', 'fog',
                'snow', 'ice', 'wind', 'lightning', 'turbulence',
                'grounded', 'runway', 'terminal',
                // Spanish
                'aeropuerto', 'vuelo', 'aerolinea', 'retraso',
                'cancelacion', 'cancelado', 'tormenta', 'huracan',
                'inundacion', 'niebla', 'nieve', 'viento', 'rayo',
                'pista', 'desvio', 'terminal',
            ]),
        ];
    }

    // ─── Feed registry ────────────────────────────────────────────────────────

    /**
     * Full feed registry.
     * Each entry: iata, name, url, language, priority, notes
     *
     * @return list<array{iata:string|null,name:string,url:string,language:string,priority:int,notes:string|null,is_active:bool}>
     */
    private function sources(): array
    {
        return [

            // ══════════════════════════════════════════════════════════════════
            // GLOBAL DEFAULTS  (iata = null — fetched for every watch target)
            // ══════════════════════════════════════════════════════════════════

            [
                'iata' => null, 'language' => 'en', 'priority' => 9, 'is_active' => true,
                'name' => 'Simple Flying',
                'url'  => 'https://simpleflying.com/feed/',
                'notes' => 'High-volume aviation news, airline ops, airport disruptions.',
            ],
            [
                'iata' => null, 'language' => 'en', 'priority' => 9, 'is_active' => true,
                'name' => 'The Aviation Herald',
                'url'  => 'https://avherald.com/index.php?rss',
                'notes' => 'Incident and disruption reports; authoritative source.',
            ],
            [
                'iata' => null, 'language' => 'en', 'priority' => 8, 'is_active' => true,
                'name' => 'AeroTime Hub',
                'url'  => 'https://www.aerotime.aero/feed',
                'notes' => 'Airline and airport operations news.',
            ],
            [
                'iata' => null, 'language' => 'en', 'priority' => 8, 'is_active' => true,
                'name' => 'ch-aviation',
                'url'  => 'https://www.ch-aviation.com/portal/news/rss',
                'notes' => 'Airline capacity, fleet, and operational changes.',
            ],
            [
                'iata' => null, 'language' => 'en', 'priority' => 7, 'is_active' => true,
                'name' => 'FlightAware News',
                'url'  => 'https://flightaware.com/news/rss',
                'notes' => 'FlightAware public blog and service notices.',
            ],
            [
                'iata' => null, 'language' => 'en', 'priority' => 7, 'is_active' => true,
                'name' => 'The Points Guy — Aviation',
                'url'  => 'https://thepointsguy.com/news/aviation/feed/',
                'notes' => 'Airline news, route changes, and disruptions.',
            ],
            [
                'iata' => null, 'language' => 'en', 'priority' => 6, 'is_active' => true,
                'name' => 'Skift Aviation',
                'url'  => 'https://skift.com/aviation/feed/',
                'notes' => 'Industry-level airline and airport news.',
            ],

            // ══════════════════════════════════════════════════════════════════
            // MEXICO
            // ══════════════════════════════════════════════════════════════════

            // ── CUN — Cancún International ────────────────────────────────────
            [
                'iata' => 'CUN', 'language' => 'es', 'priority' => 8, 'is_active' => true,
                'name' => 'Noticaribe',
                'url'  => 'https://www.noticaribe.com.mx/feed/',
                'notes' => 'Quintana Roo / Cancún regional news; covers hurricanes and local disruptions.',
            ],
            [
                'iata' => 'CUN', 'language' => 'es', 'priority' => 7, 'is_active' => true,
                'name' => 'Por Esto! Quintana Roo',
                'url'  => 'https://www.poresto.net/feed/',
                'notes' => 'Yucatan Peninsula print/digital paper.',
            ],
            [
                'iata' => 'CUN', 'language' => 'es', 'priority' => 7, 'is_active' => true,
                'name' => 'Novedades Quintana Roo',
                'url'  => 'https://novedadesqroo.com.mx/feed/',
                'notes' => 'Cancún-based news daily.',
            ],
            [
                'iata' => 'CUN', 'language' => 'en', 'priority' => 6, 'is_active' => true,
                'name' => 'Riviera Maya Life',
                'url'  => 'https://www.rivieramayalife.com/feed/',
                'notes' => 'English-language tourism and disruption coverage for the Riviera Maya.',
            ],
            [
                'iata' => 'CUN', 'language' => 'en', 'priority' => 6, 'is_active' => true,
                'name' => 'Cancún Magazine',
                'url'  => 'https://cancun.com/feed/',
                'notes' => 'Travel conditions and destination updates.',
            ],

            // ── SJD — Los Cabos International ────────────────────────────────
            [
                'iata' => 'SJD', 'language' => 'en', 'priority' => 8, 'is_active' => true,
                'name' => 'Los Cabos Guide News',
                'url'  => 'https://www.loscabosguide.com/news/feed/',
                'notes' => 'English-language Cabo news; covers weather events and travel conditions.',
            ],
            [
                'iata' => 'SJD', 'language' => 'en', 'priority' => 7, 'is_active' => true,
                'name' => 'The Cabo Sun',
                'url'  => 'https://thecabosun.com/feed/',
                'notes' => 'English bilingual local newspaper for Los Cabos.',
            ],
            [
                'iata' => 'SJD', 'language' => 'es', 'priority' => 7, 'is_active' => true,
                'name' => 'BCS Noticias',
                'url'  => 'https://www.bcsnoticias.mx/feed/',
                'notes' => 'Baja California Sur state news; storm and infrastructure coverage.',
            ],
            [
                'iata' => 'SJD', 'language' => 'en', 'priority' => 6, 'is_active' => true,
                'name' => 'Cabo San Lucas Life',
                'url'  => 'https://cabosanlucaslife.com/feed/',
                'notes' => 'Expat-oriented Cabo news including weather and travel.',
            ],

            // ── MEX — Benito Juárez International, Mexico City ────────────────
            [
                'iata' => 'MEX', 'language' => 'es', 'priority' => 8, 'is_active' => true,
                'name' => 'El Universal',
                'url'  => 'https://www.eluniversal.com.mx/rss.xml',
                'notes' => 'Major Mexico City daily; strong AICM/NAICM airport coverage.',
            ],
            [
                'iata' => 'MEX', 'language' => 'es', 'priority' => 8, 'is_active' => true,
                'name' => 'Milenio',
                'url'  => 'https://www.milenio.com/rss',
                'notes' => 'National daily; strong transport and weather beat.',
            ],
            [
                'iata' => 'MEX', 'language' => 'es', 'priority' => 7, 'is_active' => true,
                'name' => 'La Jornada',
                'url'  => 'https://www.jornada.com.mx/ultimas/rss.xml',
                'notes' => 'National; covers AICM disruptions closely.',
            ],
            [
                'iata' => 'MEX', 'language' => 'es', 'priority' => 7, 'is_active' => true,
                'name' => 'Excélsior',
                'url'  => 'https://www.excelsior.com.mx/rss.xml',
                'notes' => 'National broadsheet with Mexico City infrastructure desk.',
            ],
            [
                'iata' => 'MEX', 'language' => 'es', 'priority' => 6, 'is_active' => true,
                'name' => 'Reforma',
                'url'  => 'https://gruporeforma-blogs.com/noticias/feed/',
                'notes' => 'Influential Mexico City paper.',
            ],

            // ── MID — Mérida Manuel Crescencio Rejón ─────────────────────────
            [
                'iata' => 'MID', 'language' => 'es', 'priority' => 8, 'is_active' => true,
                'name' => 'El Diario de Yucatán',
                'url'  => 'https://yucatan.com.mx/feed',
                'notes' => 'Top regional newspaper for the Yucatan.',
            ],
            [
                'iata' => 'MID', 'language' => 'es', 'priority' => 7, 'is_active' => true,
                'name' => 'Por Esto! Mérida',
                'url'  => 'https://www.poresto.net/feed/',
                'notes' => 'Yucatan Peninsula regional news.',
            ],
            [
                'iata' => 'MID', 'language' => 'es', 'priority' => 6, 'is_active' => true,
                'name' => 'Milenio Noroeste',
                'url'  => 'https://www.milenio.com/rss',
                'notes' => 'National daily with Yucatan desk.',
            ],

            // ── MTY — Monterrey General Mariano Escobedo ─────────────────────
            [
                'iata' => 'MTY', 'language' => 'es', 'priority' => 8, 'is_active' => true,
                'name' => 'El Norte',
                'url'  => 'https://www.elnorte.com/rss/portada.xml',
                'notes' => "Monterrey's leading newspaper.",
            ],
            [
                'iata' => 'MTY', 'language' => 'es', 'priority' => 7, 'is_active' => true,
                'name' => 'Milenio Monterrey',
                'url'  => 'https://www.milenio.com/rss',
                'notes' => 'National daily with strong Monterrey coverage.',
            ],

            // ── GDL — Guadalajara Don Miguel Hidalgo ─────────────────────────
            [
                'iata' => 'GDL', 'language' => 'es', 'priority' => 8, 'is_active' => true,
                'name' => 'El Informador',
                'url'  => 'https://www.informador.mx/rss',
                'notes' => "Guadalajara's paper of record.",
            ],
            [
                'iata' => 'GDL', 'language' => 'es', 'priority' => 7, 'is_active' => true,
                'name' => 'Mural',
                'url'  => 'https://mural.com.mx/rss/portada.xml',
                'notes' => 'Guadalajara Reforma affiliate.',
            ],
            [
                'iata' => 'GDL', 'language' => 'es', 'priority' => 6, 'is_active' => true,
                'name' => 'Milenio Jalisco',
                'url'  => 'https://www.milenio.com/rss',
                'notes' => 'National daily with Jalisco desk.',
            ],

            // ── PVR — Puerto Vallarta Gustavo Díaz Ordaz ─────────────────────
            [
                'iata' => 'PVR', 'language' => 'en', 'priority' => 8, 'is_active' => true,
                'name' => 'Vallarta Daily News',
                'url'  => 'https://www.vallartadaily.com/feed/',
                'notes' => 'English-language local paper for Puerto Vallarta.',
            ],
            [
                'iata' => 'PVR', 'language' => 'en', 'priority' => 7, 'is_active' => true,
                'name' => 'Banderas News',
                'url'  => 'https://www.banderasnews.com/feed/',
                'notes' => 'Bahia de Banderas / Vallarta region news.',
            ],
            [
                'iata' => 'PVR', 'language' => 'en', 'priority' => 6, 'is_active' => true,
                'name' => 'Puerto Vallarta News',
                'url'  => 'https://www.pvdn.com/feed/',
                'notes' => 'English-language Puerto Vallarta local news.',
            ],

            // ── TIJ — Tijuana General Abelardo L. Rodríguez ──────────────────
            [
                'iata' => 'TIJ', 'language' => 'es', 'priority' => 8, 'is_active' => true,
                'name' => 'El Imparcial de Tijuana',
                'url'  => 'https://www.elimparcial.com/rss/tijuana.xml',
                'notes' => 'Tijuana regional daily.',
            ],
            [
                'iata' => 'TIJ', 'language' => 'es', 'priority' => 7, 'is_active' => true,
                'name' => 'Frontera',
                'url'  => 'https://www.frontera.info/rss',
                'notes' => "Tijuana's print daily.",
            ],
            [
                'iata' => 'TIJ', 'language' => 'es', 'priority' => 6, 'is_active' => true,
                'name' => 'Zeta Tijuana',
                'url'  => 'https://zetatijuana.com/feed/',
                'notes' => 'Investigative / regional Baja California coverage.',
            ],

            // ══════════════════════════════════════════════════════════════════
            // UNITED STATES
            // ══════════════════════════════════════════════════════════════════

            // ── MIA — Miami International ─────────────────────────────────────
            [
                'iata' => 'MIA', 'language' => 'en', 'priority' => 8, 'is_active' => true,
                'name' => 'Miami Herald',
                'url'  => 'https://www.miamiherald.com/news/rss.xml',
                'notes' => 'Covers MIA disruptions and South Florida weather extensively.',
            ],
            [
                'iata' => 'MIA', 'language' => 'en', 'priority' => 7, 'is_active' => true,
                'name' => 'Local10 (WPLG) Breaking',
                'url'  => 'https://www.local10.com/rss/news/',
                'notes' => 'ABC Miami breaking news; strong hurricane/weather coverage.',
            ],
            [
                'iata' => 'MIA', 'language' => 'en', 'priority' => 7, 'is_active' => true,
                'name' => 'NBC6 Miami',
                'url'  => 'https://www.nbcmiami.com/news/local/feed/',
                'notes' => 'NBC Miami breaking news.',
            ],
            [
                'iata' => 'MIA', 'language' => 'en', 'priority' => 6, 'is_active' => true,
                'name' => 'CBS4 Miami',
                'url'  => 'https://miami.cbslocal.com/feed/',
                'notes' => 'CBS Miami breaking news.',
            ],

            // ── JFK — John F. Kennedy International ──────────────────────────
            [
                'iata' => 'JFK', 'language' => 'en', 'priority' => 8, 'is_active' => true,
                'name' => 'NY Post',
                'url'  => 'https://nypost.com/feed/',
                'notes' => 'Popular NY paper; covers JFK/LGA delays.',
            ],
            [
                'iata' => 'JFK', 'language' => 'en', 'priority' => 7, 'is_active' => true,
                'name' => 'NY Daily News',
                'url'  => 'https://www.nydailynews.com/arcio/rss/category/news/',
                'notes' => 'New York metro news.',
            ],
            [
                'iata' => 'JFK', 'language' => 'en', 'priority' => 7, 'is_active' => true,
                'name' => 'ABC7 New York',
                'url'  => 'https://abc7ny.com/feed/',
                'notes' => 'ABC New York breaking news.',
            ],
            [
                'iata' => 'JFK', 'language' => 'en', 'priority' => 6, 'is_active' => true,
                'name' => 'CBS News US',
                'url'  => 'https://www.cbsnews.com/latest/rss/us',
                'notes' => 'National breaking news with strong NY airport coverage.',
            ],

            // ── ATL — Hartsfield-Jackson Atlanta ─────────────────────────────
            [
                'iata' => 'ATL', 'language' => 'en', 'priority' => 8, 'is_active' => true,
                'name' => 'Atlanta Journal-Constitution',
                'url'  => 'https://www.ajc.com/news/?outputType=rss',
                'notes' => 'Strong weather and ATL airport coverage.',
            ],
            [
                'iata' => 'ATL', 'language' => 'en', 'priority' => 7, 'is_active' => true,
                'name' => 'WSB-TV (ABC Atlanta)',
                'url'  => 'https://www.wsbtv.com/rss/news/',
                'notes' => 'Atlanta ABC breaking news.',
            ],
            [
                'iata' => 'ATL', 'language' => 'en', 'priority' => 6, 'is_active' => true,
                'name' => '11Alive (NBC Atlanta)',
                'url'  => 'https://rss.11alive.com/rss/news/',
                'notes' => 'NBC Atlanta breaking news.',
            ],

            // ── ORD — O'Hare International, Chicago ──────────────────────────
            [
                'iata' => 'ORD', 'language' => 'en', 'priority' => 8, 'is_active' => true,
                'name' => 'Chicago Tribune',
                'url'  => 'https://www.chicagotribune.com/arc/outbound/feed/rss/topic/news/',
                'notes' => "Top Chicago paper; O'Hare delay and winter weather coverage.",
            ],
            [
                'iata' => 'ORD', 'language' => 'en', 'priority' => 7, 'is_active' => true,
                'name' => 'WGN-TV Chicago',
                'url'  => 'https://wgntv.com/feed/',
                'notes' => 'WGN Chicago breaking news.',
            ],
            [
                'iata' => 'ORD', 'language' => 'en', 'priority' => 6, 'is_active' => true,
                'name' => 'ABC7 Chicago',
                'url'  => 'https://abc7chicago.com/feeds/feed.rss',
                'notes' => 'ABC Chicago breaking news.',
            ],

            // ── DFW — Dallas/Fort Worth International ─────────────────────────
            [
                'iata' => 'DFW', 'language' => 'en', 'priority' => 8, 'is_active' => true,
                'name' => 'Dallas Morning News',
                'url'  => 'https://www.dallasnews.com/arcio/rss/category/news/',
                'notes' => 'DFW airport and North Texas weather coverage.',
            ],
            [
                'iata' => 'DFW', 'language' => 'en', 'priority' => 7, 'is_active' => true,
                'name' => 'WFAA (ABC Dallas)',
                'url'  => 'https://www.wfaa.com/feeds/syndication/rss/news/',
                'notes' => 'Dallas ABC breaking news.',
            ],
            [
                'iata' => 'DFW', 'language' => 'en', 'priority' => 6, 'is_active' => true,
                'name' => 'NBC 5 Dallas-Fort Worth',
                'url'  => 'https://www.nbcdfw.com/news/local/feed/',
                'notes' => 'NBC Dallas breaking news.',
            ],

            // ── IAH — George Bush Intercontinental, Houston ───────────────────
            [
                'iata' => 'IAH', 'language' => 'en', 'priority' => 8, 'is_active' => true,
                'name' => 'Houston Chronicle',
                'url'  => 'https://www.chron.com/rss/feed/',
                'notes' => 'Houston metro news; strong hurricane/tropical storm coverage.',
            ],
            [
                'iata' => 'IAH', 'language' => 'en', 'priority' => 7, 'is_active' => true,
                'name' => 'KHOU-11 (CBS Houston)',
                'url'  => 'https://www.khou.com/feeds/syndication/rss/news/',
                'notes' => 'CBS Houston breaking news.',
            ],
            [
                'iata' => 'IAH', 'language' => 'en', 'priority' => 6, 'is_active' => true,
                'name' => 'ABC13 Houston',
                'url'  => 'https://abc13.com/feed/',
                'notes' => 'ABC Houston breaking news.',
            ],

            // ── LAX — Los Angeles International ──────────────────────────────
            [
                'iata' => 'LAX', 'language' => 'en', 'priority' => 8, 'is_active' => true,
                'name' => 'Los Angeles Times',
                'url'  => 'https://www.latimes.com/rss2.0.xml',
                'notes' => 'Aviation and weather desk; covers LAX extensively.',
            ],
            [
                'iata' => 'LAX', 'language' => 'en', 'priority' => 7, 'is_active' => true,
                'name' => 'KABC (ABC7 LA)',
                'url'  => 'https://abc7.com/feed/',
                'notes' => 'ABC Los Angeles breaking news.',
            ],
            [
                'iata' => 'LAX', 'language' => 'en', 'priority' => 6, 'is_active' => true,
                'name' => 'NBC Los Angeles',
                'url'  => 'https://www.nbclosangeles.com/news/local/feed/',
                'notes' => 'NBC LA breaking news.',
            ],

            // ── SFO — San Francisco International ────────────────────────────
            [
                'iata' => 'SFO', 'language' => 'en', 'priority' => 8, 'is_active' => true,
                'name' => 'San Francisco Chronicle',
                'url'  => 'https://sfchronicle.com/arcio/rss/category/news/',
                'notes' => 'Covers SFO disruptions, Bay Area weather, and fog advisories.',
            ],
            [
                'iata' => 'SFO', 'language' => 'en', 'priority' => 7, 'is_active' => true,
                'name' => 'ABC7 Bay Area',
                'url'  => 'https://abc7news.com/feed/',
                'notes' => 'ABC Bay Area breaking news.',
            ],
            [
                'iata' => 'SFO', 'language' => 'en', 'priority' => 6, 'is_active' => true,
                'name' => 'NBC Bay Area',
                'url'  => 'https://www.nbcbayarea.com/news/local/feed/',
                'notes' => 'NBC Bay Area breaking news.',
            ],

            // ══════════════════════════════════════════════════════════════════
            // CANADA
            // ══════════════════════════════════════════════════════════════════

            // ── YYZ — Toronto Pearson International ───────────────────────────
            [
                'iata' => 'YYZ', 'language' => 'en', 'priority' => 8, 'is_active' => true,
                'name' => 'Toronto Star',
                'url'  => 'https://www.thestar.com/news.rss',
                'notes' => 'Covers Pearson delays and Toronto weather.',
            ],
            [
                'iata' => 'YYZ', 'language' => 'en', 'priority' => 7, 'is_active' => true,
                'name' => 'CTV News Toronto',
                'url'  => 'https://toronto.ctvnews.ca/rss/ctv-news-toronto-1.822694',
                'notes' => 'Toronto CTV breaking news.',
            ],
            [
                'iata' => 'YYZ', 'language' => 'en', 'priority' => 7, 'is_active' => true,
                'name' => 'CBC Toronto',
                'url'  => 'https://www.cbc.ca/cmlink/rss-canada-toronto',
                'notes' => 'CBC Toronto news.',
            ],
            [
                'iata' => 'YYZ', 'language' => 'en', 'priority' => 6, 'is_active' => true,
                'name' => 'Global News Toronto',
                'url'  => 'https://globalnews.ca/toronto/feed/',
                'notes' => 'Global News Toronto.',
            ],

            // ── YVR — Vancouver International ─────────────────────────────────
            [
                'iata' => 'YVR', 'language' => 'en', 'priority' => 8, 'is_active' => true,
                'name' => 'Vancouver Sun',
                'url'  => 'https://vancouversun.com/feed',
                'notes' => 'Covers YVR and BC weather.',
            ],
            [
                'iata' => 'YVR', 'language' => 'en', 'priority' => 7, 'is_active' => true,
                'name' => 'CTV News BC',
                'url'  => 'https://bc.ctvnews.ca/rss/ctv-news-bc-1.822769',
                'notes' => 'BC CTV breaking news.',
            ],
            [
                'iata' => 'YVR', 'language' => 'en', 'priority' => 7, 'is_active' => true,
                'name' => 'CBC British Columbia',
                'url'  => 'https://www.cbc.ca/cmlink/rss-canada-britishcolumbia',
                'notes' => 'CBC BC news.',
            ],
            [
                'iata' => 'YVR', 'language' => 'en', 'priority' => 6, 'is_active' => true,
                'name' => 'Global News Vancouver',
                'url'  => 'https://globalnews.ca/vancouver/feed/',
                'notes' => 'Global News Vancouver.',
            ],

            // ── YUL — Montréal-Trudeau International ──────────────────────────
            [
                'iata' => 'YUL', 'language' => 'en', 'priority' => 8, 'is_active' => true,
                'name' => 'Montreal Gazette',
                'url'  => 'https://montrealgazette.com/feed',
                'notes' => 'Covers YUL and Montreal weather.',
            ],
            [
                'iata' => 'YUL', 'language' => 'en', 'priority' => 7, 'is_active' => true,
                'name' => 'CTV News Montreal',
                'url'  => 'https://montreal.ctvnews.ca/rss/ctv-news-montreal-1.822661',
                'notes' => 'Montreal CTV breaking news.',
            ],
            [
                'iata' => 'YUL', 'language' => 'en', 'priority' => 7, 'is_active' => true,
                'name' => 'CBC Montreal',
                'url'  => 'https://www.cbc.ca/cmlink/rss-canada-montreal',
                'notes' => 'CBC Montreal news.',
            ],
            [
                'iata' => 'YUL', 'language' => 'fr', 'priority' => 6, 'is_active' => true,
                'name' => 'Radio-Canada Montréal',
                'url'  => 'https://ici.radio-canada.ca/rss/4201',
                'notes' => 'French-language CBC Montreal.',
            ],

            // ── YYC — Calgary International ───────────────────────────────────
            [
                'iata' => 'YYC', 'language' => 'en', 'priority' => 8, 'is_active' => true,
                'name' => 'Calgary Herald',
                'url'  => 'https://calgaryherald.com/feed',
                'notes' => 'Covers YYC and Alberta weather.',
            ],
            [
                'iata' => 'YYC', 'language' => 'en', 'priority' => 7, 'is_active' => true,
                'name' => 'CTV News Calgary',
                'url'  => 'https://calgary.ctvnews.ca/rss/ctv-news-calgary-1.822679',
                'notes' => 'Calgary CTV breaking news.',
            ],
            [
                'iata' => 'YYC', 'language' => 'en', 'priority' => 6, 'is_active' => true,
                'name' => 'CBC Calgary',
                'url'  => 'https://www.cbc.ca/cmlink/rss-canada-calgary',
                'notes' => 'CBC Calgary news.',
            ],

        ];
    }
}
