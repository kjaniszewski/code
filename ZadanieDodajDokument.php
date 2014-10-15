<?php

namespace Meddo\API\MeddoBundle\Zadania;

use Meddo\API\BazoweZadanie;
use Meddo\KomponentyBundle\Model\Meddo\DokumentyQuery;
use Symfony\Component\Validator\ExecutionContext;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\Callback(methods={"walidujUrlBadania"})
 * @Assert\Callback(methods={"walidujUnikalnoscIdentyfikatora"})
 */
class ZadanieDodajDokument extends BazoweZadanie
{
    /**
     * @Assert\NotBlank(message="Nie przekazano wartości pola identyfikator")
     * @Assert\NotNull(message="Pole 'identyfikator' jest wymagane")
     *
     * @var int
     */
    public $identyfikator;

    /**
     * @var string
     */
    public $flaga_kontrolna;

    /**
     * @Assert\Url(message="Podany adres paczki jest nieprawidłowy")
     *
     * @var string
     */
    public $url_paczki;

    /**
     * @var string
     */
    public $url_badania;

    /**
     * @Assert\Url(message="Podany adres miniaturki jest nieprawidłowy")
     *
     * @var string
     */
    public $url_miniaturki;

    /**
     * {@inheritdoc}
     */
    public function obslugiwanaKlasa()
    {
        return 'Meddo\API\MeddoBundle\Zadania\ZadanieDodajDokument';
    }

    /**
     * {@inheritdoc}
     */
    public function pobierzUrlAkcji()
    {
        return '/api/dokumenty/dodaj';
    }

    /**
     * {@inheritdoc}
     */
    public function daneWyjsciowePoprawne($dane)
    {
        if (is_array($dane) || is_bool($dane)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Sprawdza poprawność parametru URL badania
     *
     * @param \Symfony\Component\Validator\ExecutionContext $ec Kontekst wywołania
     *
     * @return null
     */
    public function walidujUrlBadania(ExecutionContext $ec)
    {
        if ($this->url_badania != '') {
            $walidatorUrl = new Assert\Url();

            $bledy = $this->kontener->get('validator')->validateValue(
                'http://' . $this->url_badania,
                $walidatorUrl
            );

            if (count($bledy) > 0) {
                $ec->addViolationAt('url_badania', "Link do badania jest niepoprawny");
            }
        }
    }

    /**
     * Sprawdza unikalność identyfikatora dokumentu
     *
     * @param \Symfony\Component\Validator\ExecutionContext $ec Kontekst wywołania
     *
     * @return null
     */
    public function walidujUnikalnoscIdentyfikatora(ExecutionContext $ec)
    {
        if ($this->identyfikator != '') {
            $ilosc = DokumentyQuery::create()
                ->filterByIdentyfikator($this->identyfikator)
                ->count();

            if ($ilosc > 0) {
                $ec->addViolationAt('identyfikator', "Dokument z takim identyfikatore już istnieje");
            }
        }
    }

}
