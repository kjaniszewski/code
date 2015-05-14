<?php

namespace Ecenter\KomponentyBundle\Twig\Rozszerzenia;

use Ecenter\KomponentyBundle\Model\CMS\EcObjrObiektyJezykiQuery;
use Ecenter\KomponentyBundle\Model\CMS\EcCmsStronyQuery;
use Ecenter\KomponentyBundle\Model\CMS\EcObjrObiektyKategorieQuery;
use Ecenter\KomponentyBundle\Model\System\EcCrmiPozycjeCennikaQuery;
use Ecenter\KomponentyBundle\Model\System\EcDomnTldQuery;
use Ecenter\KomponentyBundle\Model\System\EcDomnTldTlumaczeniaQuery;
use Ecenter\KomponentyBundle\Model\System\EcI18nJezykiQuery;

class Domeny extends \Twig_Extension
{

    private $_kontener;
    private $_daneStron;

    /**
     * konstruktor obiektu
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $kontener Kontener serwisów
     *
     * @return Domeny
     */
    public function __construct($kontener)
    {
        $this->_kontener = $kontener;
        $this->_daneStron = array("_nieistotne" => "");
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            'lista_domen'   => new \Twig_Function_Method($this, 'listaDomen'),
            'pobierz_tld'   => new \Twig_Function_Method($this, 'pobierzTld')
        );
    }

    /**
     * Pobiera listę TLD i przekazuje ją do zmiennej
     *
     * @param int $idGrupyTld Grupa TLD z której zostaną pobrane domeny
     *
     * @return array
     */
    public function listaDomen($idGrupyTld)
    {
        $locale = $this->_kontener->get('request')->getLocale();
        $idWaluty = $this->_kontener->get('session')->get('id_waluty');
        $jezyk = EcObjrObiektyJezykiQuery::create()->findOneByIdentyfikator($locale);
        
        if ($jezyk == null) {
            // TODO: obsługa błędu
            return;
        }

        $listaTld = EcDomnTldQuery::create()
            ->filterByIdGrupyTld($idGrupyTld, \Criteria::IN)
            ->useEcCrmiUslugiQuery()
                ->useEcCrmiPozycjeCennikaQuery()
                    ->joinEcFnseWaluty()
                    ->addJoinCondition('EcFnseWaluty', 'EcFnseWaluty.IdWaluty=?', $idWaluty)
                ->endUse()
            ->endUse()
            ->addJoinCondition('EcCrmiPozycjeCennika', 'ec_crmi_pozycje_cennika.okres_abonamentowy=ec_domn_tld.minimalny_okres_rejestracji')
            ->addJoinCondition('EcCrmiPozycjeCennika', 'ec_crmi_pozycje_cennika.id_waluty=?', $idWaluty, null, \PDO::PARAM_INT)
            ->orderBySkrot()
            ->joinEcCrmiKraje()
            ->with('EcCrmiKraje')
            ->orderBy('EcCrmiKraje.Nazwa')
            ->withColumn('EcCrmiPozycjeCennika.Cena', 'cena')
            ->withColumn('EcFnseWaluty.Symbol', 'waluta')
            ->withColumn('EcCrmiKraje.NazwaDoDruku', 'kraj')
            ->find();

        $daneTld = array();
        /** @var $tld \Ecenter\KomponentyBundle\Model\System\EcDomnTld */
        foreach ($listaTld as $tld) {
            $tmp = $tld->toArray();
            $tmp = array_merge($tmp, $tld->getVirtualColumns());
            $daneTld[] = $tmp;
        }
        
        return $daneTld;
    }

    /**
     * Pobiera informacje o pojedynczym TLD
     *
     * @param int $id TLD do pobrania
     *
     * @return array
     */
    public function pobierzTld($id)
    {
        $locale = $this->_kontener->get('request')->getLocale();
        $idWaluty = $this->_kontener->get('session')->get('id_waluty');
        $jezyk = EcI18nJezykiQuery::create()->findOneBySkrot($locale);

        if ($jezyk == null) {
            // TODO: obsługa błędu
            return;
        }

        /** @var $tld \Ecenter\KomponentyBundle\Model\System\EcDomnTld */
        $tld = EcDomnTldQuery::create()
            ->filterByIdTld($id)
            ->useEcCrmiUslugiQuery()
                ->useEcCrmiPozycjeCennikaQuery()
                    ->joinEcFnseWaluty()
                    ->addJoinCondition('EcFnseWaluty', 'EcFnseWaluty.IdWaluty=?', $idWaluty)
                ->endUse()
            ->endUse()
            ->addJoinCondition('EcCrmiPozycjeCennika', 'ec_crmi_pozycje_cennika.okres_abonamentowy=ec_domn_tld.minimalny_okres_rejestracji')
            ->addJoinCondition('EcCrmiPozycjeCennika', 'ec_crmi_pozycje_cennika.id_waluty=?', $idWaluty, null, \PDO::PARAM_INT)
            ->orderBySkrot()
            ->joinEcCrmiKraje()
            ->with('EcCrmiKraje')
            ->orderBy('EcCrmiKraje.Nazwa')
            ->withColumn('EcCrmiPozycjeCennika.Cena', 'cena')
            ->withColumn('EcFnseWaluty.Symbol', 'waluta')
            ->withColumn('EcCrmiKraje.NazwaDoDruku', 'kraj')
            ->findOne();

        $uslugaRejestracji = $tld->getEcCrmiUslugi();
        $uslugaOdnowienia = $tld->getEcCrmiUslugi()->getEcCrmiUslugiRelatedByIdUslugiOdnowienia();

        $daneTld = array();
        $daneTld = $tld->toArray();
        $daneTld = array_merge($daneTld, $tld->getVirtualColumns());

        $cenyRejestracji = EcCrmiPozycjeCennikaQuery::create()
            ->filterByEcCrmiUslugi($uslugaRejestracji)
            ->orderByOkresAbonamentowy()
            ->innerJoinEcFnseWaluty()
            ->addJoinCondition('EcFnseWaluty', 'EcFnseWaluty.IdWaluty=?', $idWaluty)
            ->with('EcFnseWaluty')
            ->find();

        $cenyOdnowien = EcCrmiPozycjeCennikaQuery::create()
            ->filterByEcCrmiUslugi($uslugaOdnowienia)
            ->innerJoinEcFnseWaluty()
            ->addJoinCondition('EcFnseWaluty', 'EcFnseWaluty.IdWaluty=?', $idWaluty)
            ->with('EcFnseWaluty')
            ->orderByOkresAbonamentowy()
            ->find();

        $tlumaczenia = EcDomnTldTlumaczeniaQuery::create()
            ->filterByEcDomnTld($tld)
            ->filterByEcI18nJezyki($jezyk)
            ->findOne();

        $daneTld = array_merge($daneTld, $tlumaczenia->toArray(), array('ceny_rejestracji' => $cenyRejestracji->toArray()), array('ceny_odnowien' => $cenyOdnowien->toArray()));

        return $daneTld;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'domeny';
    }
}
?>