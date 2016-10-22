<?php

namespace Terranet\Localizer\Resolvers;

use Illuminate\Http\Request;
use Terranet\Localizer\Contracts\Resolver;

class RequestResolver implements Resolver
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var mixed null|array
     */
    static protected $languages = null;

    /**
     * RequestResolver constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Resolve locale
     *
     * @return mixed
     */
    public function resolve()
    {
        if ($locale = $this->request->segment(config('localizer.request.segment', 1))) {
            return $locale;
        }

        return $this->resolveHeader();
    }

    /**
     * Resolve language using HTTP_ACCEPT_LANGUAGE header
     * which is used mostly by API requests
     *
     * @return array|null
     */
    private function resolveHeader()
    {
        if (null === static::$languages) {
            $httpLanguages = $this->request->server(config('localizer.request.header', 'HTTP_ACCEPT_LANGUAGE'));

            if (empty($httpLanguages)) {
                static::$languages = [];
            } else {
                $accepted = preg_split('/,\s*/', $httpLanguages);

                static::$languages = empty($languages = $this->buildCollection($accepted, $languages = []))
                    ? null
                    : array_keys($languages)[0];
            }
        }

        return static::$languages;
    }

    /**
     * @param $accepted
     * @param $languages
     * @return mixed
     */
    private function buildCollection($accepted, $languages)
    {
        foreach ($accepted as $accept) {
            $match = null;
            $result = preg_match(
                '/^([a-z]{1,8}(?:[-_][a-z]{1,8})*)(?:;\s*q=(0(?:\.[0-9]{1,3})?|1(?:\.0{1,3})?))?$/i',
                $accept,
                $match
            );
            if ($result < 1) {
                continue;
            }
            if (isset($match[2]) === true) {
                $quality = (float) $match[2];
            } else {
                $quality = 1.0;
            }
            $countries = explode('-', $match[1]);
            $region = array_shift($countries);
            $country2 = explode('_', $region);
            $region = array_shift($country2);

            foreach ($countries as $country) {
                $languages[$region . '_' . strtoupper($country)] = $quality;
            }

            foreach ($country2 as $country) {
                $languages[$region . '_' . strtoupper($country)] = $quality;
            }

            if ((isset($languages[$region]) === false) || ($languages[$region] < $quality)) {
                $languages[$region] = $quality;
            }
        }

        return $languages;
    }

    /**
     * Re-Assemble current url with different locale.
     *
     * @param $iso
     * @param null $url
     * @return mixed
     */
    public function assemble($iso, $url = null)
    {
        return url($iso . '/' . ltrim($url, '/'));
    }
}
