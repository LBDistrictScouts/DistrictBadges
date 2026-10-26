<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use App\Model\Entity\User;
use App\Model\Table\UsersTable;
use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use LogicException;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/5/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
    private const PAGINATION_LIMITS = [10, 25, 50, 75, 100];

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('FormProtection');`
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');
        $this->loadComponent('StockTransactionLines');

        /*
         * Enable the following component for recommended CakePHP form protection settings.
         * see https://book.cakephp.org/5/en/controllers/components/form-protection.html
         */
        //$this->loadComponent('FormProtection');
    }

    /**
     * Apply the requested or session-cached page size to every paginated index.
     *
     * @param \Cake\Event\EventInterface $event Controller beforeFilter event.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        if ($this->request->getParam('prefix') === 'Api') {
            return;
        }

        $controller = (string)$this->request->getParam('controller');
        if ($controller === 'Auth') {
            return;
        }

        $session = $this->request->getSession();
        $identity = $session->read('Auth.identity');
        $user = null;
        if (
            is_array($identity)
            && is_string($identity['user_id'] ?? null)
            && is_string($identity['group_id'] ?? null)
            && is_int($identity['expires_at'] ?? null)
            && $identity['expires_at'] > time()
        ) {
            $users = $this->fetchTable('Users');
            if (!$users instanceof UsersTable) {
                throw new LogicException('The Users table must use UsersTable.');
            }

            $userEntity = $users->find()
                ->contain(['Groups'])
                ->where(['Users.id' => $identity['user_id']])
                ->first();
            $user = $userEntity instanceof User ? $userEntity : null;
        }

        if (
            $user === null
            || (string)$user->group_id !== $identity['group_id']
            || $user->group === null
        ) {
            $session->delete('Auth.identity');
            $uri = $this->request->getUri();
            $returnPath = $uri->getPath() ?: '/';
            $query = $this->request->getQueryParams();
            if ($query !== []) {
                $returnPath .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
            }
            $session->write('Auth.redirect', $returnPath);
            $event->setResult($this->redirect(['controller' => 'Auth', 'action' => 'login']));

            return;
        }

        $this->set('currentUser', $user);

        if ($this->request->getParam('action') !== 'index') {
            return;
        }

        $session = $this->request->getSession();
        $requestedLimit = filter_var($this->request->getQuery('limit'), FILTER_VALIDATE_INT);
        if ($requestedLimit !== false && in_array($requestedLimit, self::PAGINATION_LIMITS, true)) {
            $limit = $requestedLimit;
            $session->write('Pagination.limit', $limit);
        } else {
            $cachedLimit = (int)$session->read('Pagination.limit');
            $limit = in_array($cachedLimit, self::PAGINATION_LIMITS, true) ? $cachedLimit : 25;
        }

        $query = $this->request->getQueryParams();
        $query['limit'] = $limit;
        $this->setRequest($this->request->withQueryParams($query));
        $this->paginate = ['limit' => $limit, 'maxLimit' => 100] + $this->paginate;
    }

    /**
     * Validate a date-only query filter.
     *
     * @param string $value Date filter value.
     * @return string|null
     */
    protected function validDateFilter(string $value): ?string
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year) ? $value : null;
    }
}
