<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper\PaginatorHelper;
use LogicException;

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
     * @param array<array-key, mixed> $url URL options.
     * @return array<array-key, mixed>
     */
    public function generateUrlParams(array $options = [], array $url = []): array
    {
        $params = parent::generateUrlParams($options, $url);
        $query = $this->getView()->getRequest()->getQueryParams();
        unset($query['page'], $query['limit'], $query['sort'], $query['direction']);

        $queryParams = $params['?'] ?? [];
        if (!is_array($queryParams)) {
            throw new LogicException('Pagination query parameters must be an array.');
        }
        foreach ($query as $key => $value) {
            if (!array_key_exists($key, $queryParams)) {
                $queryParams[$key] = $value;
            }
        }

        $params['?'] = $queryParams;

        return $params;
    }
}
