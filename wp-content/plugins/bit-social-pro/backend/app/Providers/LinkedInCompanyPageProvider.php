<?php

namespace BitApps\SocialPro\Providers;

use BitApps\Social\HTTP\Services\Social\LinkedinService\Helper;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Client\HttpClient;

class LinkedInCompanyPageProvider
{
    public static function getLinkedinCompanyPage($apiBaseUrl, $version, $accessToken)
    {
        $header = Helper::makeHeader($accessToken);

        $data = [
            'q'          => 'roleAssignee',
            'role'       => 'ADMINISTRATOR',
            'projection' => '(elements*(organizationalTarget~(id,localizedName,vanityName,logoV2(original~:playableStreams))))'
        ];

        $company_pages = $apiBaseUrl . $version . 'organizationalEntityAcls?' . http_build_query($data);

        $httpHandler = new HttpClient();

        $rawData = $httpHandler->request($company_pages, 'GET', null, $header);
        $decodedData = json_decode(json_encode($rawData), true);

        if (!empty($decodedData['elements']) && \is_array($decodedData['elements'])) {
            return Helper::organizeCompanies($decodedData);
        }

        return [];
    }
}
