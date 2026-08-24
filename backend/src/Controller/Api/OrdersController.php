<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Exception\OrderValidationException;
use App\Service\OrderPlacementService;
use Cake\Datasource\Exception\PersistenceFailedException;

class OrdersController extends AppController
{
    /**
     * @return void
     */
    public function dependencies()
    {
        $this->request->allowMethod(['get']);

        $groups = $this->fetchTable('Groups')
            ->find()
            ->select(['id', 'group_name', 'sort_order'])
            ->orderBy(['sort_order' => 'ASC', 'group_name' => 'ASC'])
            ->enableHydration(false)
            ->toArray();

        $sections = $this->fetchTable('Sections')
            ->find()
            ->select(['id', 'group_id', 'section_name', 'section_type'])
            ->orderBy(['section_name' => 'ASC'])
            ->enableHydration(false)
            ->toArray();

        $badges = $this->fetchTable('Badges')
            ->find()
            ->select(['id', 'badge_name', 'national_product_code', 'stocked'])
            ->orderBy(['badge_name' => 'ASC'])
            ->enableHydration(false)
            ->toArray();

        $this->setResponse(
            $this->response->withHeader('Cache-Control', 'public, max-age=300, s-maxage=300'),
        );

        $this->set(compact('groups', 'sections', 'badges'));
        $this->viewBuilder()->setOption('serialize', ['groups', 'sections', 'badges']);
    }

    /**
     * @return void
     */
    public function place()
    {
        $this->request->allowMethod(['post']);

        $service = new OrderPlacementService();
        $service->setTableLocator($this->getTableLocator());
        try {
            $order = $service->place((array)$this->request->getData());
        } catch (OrderValidationException $exception) {
            $this->setResponse($this->response->withStatus(422));
            $this->set(['errors' => $exception->getErrors()]);
            $this->viewBuilder()->setOption('serialize', ['errors']);

            return;
        } catch (PersistenceFailedException $exception) {
            $this->setResponse($this->response->withStatus(500));
            $this->set([
                'error' => 'OrderCreationFailed',
                'message' => 'The order could not be created.',
            ]);
            $this->viewBuilder()->setOption('serialize', ['error', 'message']);

            return;
        }

        $this->setResponse($this->response->withStatus(201));
        $this->set([
            'status' => 'created',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);
        $this->viewBuilder()->setOption('serialize', ['status', 'order_id', 'order_number']);
    }
}
