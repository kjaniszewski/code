<?php

namespace Meddo\API\MeddoBundle\Controller;

use Meddo\API\MeddoBundle\Controller\KontrolerAPI as Cntr;
use Meddo\API\MeddoBundle\Zadania\ZadanieAktualizujDokument;
use Meddo\API\MeddoBundle\Zadania\ZadanieDodajDokument;
use Meddo\API\MeddoBundle\Zadania\ZadaniePobierzListeDokumentow;
use Meddo\API\MeddoBundle\Zadania\ZadanieUsunDokument;
use Meddo\KomponentyBundle\Model\Meddo\Dokumenty;
use Meddo\KomponentyBundle\Model\Meddo\DokumentyQuery;
use Meddo\KomponentyBundle\Model\Meddo\ZdjeciaDokumentu;
use Meddo\KomponentyBundle\Model\Meddo\ZdjeciaDokumentuQuery;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Meddo\KomponentyBundle\Odpowiedz\OdpowiedzJSON;
use Meddo\KomponentyBundle\Filtry\Filters;

/**
 * @Route("/api/dokumenty")
 */
class DokumentyController extends Cntr
{
    /**
     * Pobiera liste dokumentów
     *
     * @Route("/lista")
     * @return mixed
     */
    public function pobierzListeDokumentowAction()
    {
        /** @var $obiekt ZadaniePobierzListeDokumentow */
        $obiekt = $this->obiektZadania;

        $filtry = new Filters();
        $filtry->setFiltersFromArray($obiekt->filtry);

        $zapytanie = DokumentyQuery::create();

        if ($obiekt->identyfikator !== null) {
            $zapytanie = $zapytanie->filterByIdentyfikator($obiekt->identyfikator);
        }

        $kolumny = $filtry->receiveColumnsNames($zapytanie, 'Dokumenty');

        $rezultat['dokumenty'] = $filtry->select($zapytanie, $kolumny);
        $rezultat['ilosc']     = $filtry->count($zapytanie);

        foreach ($rezultat['dokumenty'] as $klucz => $dane) {
            $miniaturki = ZdjeciaDokumentuQuery::create()
                ->filterByIdDokumentu($dane['id_dokumentu'])
                ->find();

            $rezultat['dokumenty'][$klucz]['miniaturki'] = $miniaturki->toArray(null, false, \BasePeer::TYPE_FIELDNAME);
        }


        return new OdpowiedzJSON($rezultat);
    }

    /**
     * Dodaje dokument do bazy danych
     *
     * @Route("/dodaj")
     * @return mixed
     */

    public function dodajDokumentAction()
    {
        /** @var $obiekt ZadanieDodajDokument */
        $obiekt = $this->obiektZadania;

        $dokument = new Dokumenty();

        if ($obiekt->flaga_kontrolna == '') {
            $obiekt->flaga_kontrolna = md5(time() . rand(0, 100000));
        }
        $dokument->setIdentyfikator($obiekt->identyfikator);
        $dokument->setFlagaKontrolna($obiekt->flaga_kontrolna);
        $dokument->setUrlBadania($obiekt->url_badania);
        $dokument->setUrlZip($obiekt->url_paczki);

        try {
            $dokument->save();
            if ($obiekt->url_miniaturki != '') {
                $miniaturka = new ZdjeciaDokumentu();
                $miniaturka->setLink($obiekt->url_miniaturki);
                $miniaturka->setIdDokumentu($dokument->getIdDokumentu());
                $miniaturka->save();
            }
        } catch (\Exception $e) {
            $this->logger->emerg($e->getMessage(), array('IP klienta' => $this->zapytanie->getClientIp(), 'URL' => $this->zapytanie->getPathInfo(), 'Dane' => $this->dane));

            return new OdpowiedzJSON($this->bladOgolny($e->getMessage()));
        }

        return new OdpowiedzJSON($dokument->toArray(\BasePeer::TYPE_FIELDNAME));
    }

    /**
     * Zmienia usługę
     *
     * @Route("/aktualizuj")
     * @return mixed
     */
    public function aktualizujDokumentAction()
    {
        /** @var $obiekt ZadanieAktualizujDokument */
        $obiekt = $this->obiektZadania;

        $dokument = DokumentyQuery::create()
            ->filterByIdentyfikator($obiekt->identyfikator)
            ->filterByFlagaKontrolna($obiekt->flaga_kontrolna)
            ->findOne();

        if ($dokument === null) {
            return new OdpowiedzJSON($this->bladOgolny('Nie znaleziono dokumentu o podanych parametrach'));
        }

        if ($obiekt->url_badania != '') {
            $dokument->setUrlBadania($obiekt->url_badania);
        }
        if ($obiekt->url_paczki != '') {
            $dokument->setUrlZip($obiekt->url_paczki);
        }

        try {
            $dokument->save();

            if ($obiekt->url_miniaturki != '') {
                $miniaturka = ZdjeciaDokumentuQuery::create()
                    ->filterByIdDokumentu($dokument->getIdDokumentu())
                    ->filterByLink($obiekt->url_miniaturki)
                    ->findOneOrCreate();

                $miniaturka->save();
            }
        } catch (\Exception $e) {
            $this->logger->emerg($e->getMessage(), array('IP klienta' => $this->zapytanie->getClientIp(), 'URL' => $this->zapytanie->getPathInfo(), 'Dane' => $this->dane));

            return new OdpowiedzJSON($this->bladOgolny());
        }

        return new OdpowiedzJSON(true);
    }

    /**
     * Usuwa dokument z bazy danych
     *
     * @Route("/usun")
     * @return mixed
     */
    public function usunDokumentAction()
    {
        /** @var $obiekt ZadanieUsunDokument */
        $obiekt = $this->obiektZadania;

        $dokument = DokumentyQuery::create()
            ->filterByIdentyfikator($obiekt->identyfikator)
            ->filterByFlagaKontrolna($obiekt->flaga_kontrolna)
            ->findOne();

        if ($dokument === null) {
            return new OdpowiedzJSON($this->bladOgolny('Nie znaleziono dokumentu o podanych parametrach'));
        }

        try {
            ZdjeciaDokumentuQuery::create()
                ->filterByIdDokumentu($dokument->getIdDokumentu())
                ->delete();
            $dokument->delete();
        } catch (\Exception $e) {
            $this->logger->emerg($e->getMessage(), array('IP klienta' => $this->zapytanie->getClientIp(), 'URL' => $this->zapytanie->getPathInfo(), 'Dane' => $this->dane));

            return new OdpowiedzJSON($this->bladOgolny());
        }

        return new OdpowiedzJSON(true);
    }

}
