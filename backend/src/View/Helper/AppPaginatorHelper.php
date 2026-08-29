<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper\PaginatorHelper;

/**
 * Application paginator helper.
 *
 * CakePHP filters falsey query values from generated URLs. Restore current
 * filter values such as `0` and the empty value used by "All" options.
 */
class AppPaginatorHelper extends PaginatorHelper
{
    /**
     * @param array<string, mixed> $options Pagination/URL options.
     * @param array<string, mixed> $url URL options.
     * @return array<string, mixed>
     */
    public function generateUrlParams(array $options = [], array $url = []): array
    {
        $params = parent::generateUrlParams($options, $url);
        $query = $this->getView()->getRequest()->getQueryParams();
        unset($query['page'], $query['limit'], $query['sort'], $query['direction']);

        $params['?'] ??= [];
        foreach ($query as $key => $value) {
            if (!array_key_exists($key, $params['?'])) {
                $params['?'][$key] = $value;
            }
        }

        return $params;
    }
}
