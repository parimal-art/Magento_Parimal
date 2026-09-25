<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

class SubsActions extends Column
{
    public function __construct(
        ContextInterface              $context,
        UiComponentFactory            $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array                         $components = [],
        array                         $data = []
    )
    {
        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['subscription_id'])) {
                    $item[$this->getData('name')] = [
                        'delete' => [
                            'href' => $this->urlBuilder->getUrl(
                                'codilar_subs/subs/delete',
                                ['id' => $item['subscription_id']]
                            ),
                            'label' => __('Delete'),
                            'confirm' => [
                                'title' => __('Delete FAQ'),
                                'message' => __(
                                    'Are you sure you want to delete this FAQ?'
                                )
                            ]
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }
}
