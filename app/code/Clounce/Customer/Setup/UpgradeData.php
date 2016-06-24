<?php
/**
 * Copyright © 2015 Clounce. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Clounce\Customer\Setup;

use \Magento\Customer\Model\Customer;
use \Magento\Eav\Setup\EavSetup;
use \Magento\Customer\Setup\CustomerSetupFactory;
use \Magento\Framework\Setup\UpgradeDataInterface;
use \Magento\Framework\Setup\ModuleContextInterface;
use \Magento\Framework\Setup\ModuleDataSetupInterface;
use \Magento\Framework\Indexer\IndexerRegistry;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @param CustomerSetupFactory $customerSetupFactory
     * @param IndexerRegistry $indexerRegistry
     * @param \Magento\Eav\Model\Config $eavConfig
     */
    public function __construct(
        CustomerSetupFactory $customerSetupFactory,
        IndexerRegistry $indexerRegistry,
        \Magento\Eav\Model\Config $eavConfig
    ) {
        $this->eavSetupFactory = $customerSetupFactory;
        $this->indexerRegistry = $indexerRegistry;
        $this->eavConfig = $eavConfig;
    }

    /**
     * A central function to return the attributes that need to be created
     *
     * Updated on 15 June 2016, following Anamika comment
     **/
    private function getNewAttributes()
    {
        return [
            'custom_1' => [
                'type' => 'int',
                'label' => 'A Yes/No Option',
                'input' => 'select',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'required' => false, /* The field is not required */
                'default' => '0', /* Defaults to the No value */
                'sort_order' => 100,
                'system' => false, /* A custom attribute */
                'position' => 100,
                'adminhtml_only' => 1, /* Do not show on frontend */
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_searchable_in_grid' => false,
            ],
            'custom_2' => [
                'type' => 'varchar',
                'label' => 'Some custom text',
                'input' => 'text',
                'sort_order' => 101,
                'validate_rules' => 'a:2:{s:15:"max_text_length";i:255;s:15:"min_text_length";i:1;}',
                'position' => 101,
                'system' => false,
                /* 'adminhtml_only' => 0, --- If the attribute is visible in frontend and backend this line is not required. */
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_searchable_in_grid' => false,
            ],
        ];
    }
    /**
     * Installs data for a module
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $attributes = $this->getNewAttributes();

        foreach ($attributes as $code => $options) {
            $eavSetup->addAttribute(
                Customer::ENTITY,
                $code,
                $options
            );
        }

        $this->installCustomerForms($eavSetup);
    }

    /**
     * Add customer attributes to customer forms
     *
     * @param EavSetup $eavSetup
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function installCustomerForms(EavSetup $eavSetup)
    {
        $customer = (int)$eavSetup->getEntityTypeId(\Magento\Customer\Model\Customer::ENTITY);
        /**
         * @var ModuleDataSetupInterface $setup
         */
        $setup = $eavSetup->getSetup();

        $attributeIds = [];
        $select = $setup->getConnection()->select()->from(
            ['ea' => $setup->getTable('eav_attribute')],
            ['entity_type_id', 'attribute_code', 'attribute_id']
        )->where(
            'ea.entity_type_id IN(?)',
            [$customer]
        );
        foreach ($eavSetup->getSetup()->getConnection()->fetchAll($select) as $row) {
            $attributeIds[$row['entity_type_id']][$row['attribute_code']] = $row['attribute_id'];
        }

        $data = [];
        $attributes = $this->getNewAttributes();
        foreach ($attributes as $attributeCode => $attribute) {
            $attributeId = $attributeIds[$customer][$attributeCode];
            $attribute['system'] = isset($attribute['system']) ? $attribute['system'] : true;
            $attribute['visible'] = isset($attribute['visible']) ? $attribute['visible'] : true;
            if ($attribute['system'] != true || $attribute['visible'] != false) {
                $usedInForms = ['customer_account_create', 'customer_account_edit', 'checkout_register'];
                if (!empty($attribute['adminhtml_only'])) {
                    $usedInForms = ['adminhtml_customer'];
                } else {
                    $usedInForms[] = 'adminhtml_customer';
                }
                if (!empty($attribute['admin_checkout'])) {
                    $usedInForms[] = 'adminhtml_checkout';
                }
                foreach ($usedInForms as $formCode) {
                    $data[] = ['form_code' => $formCode, 'attribute_id' => $attributeId];
                }
            }
        }

        if ($data) {
            $setup->getConnection()
                ->insertOnDuplicate($setup->getTable('customer_form_attribute'), $data);
        }

        $indexer = $this->indexerRegistry->get(Customer::CUSTOMER_GRID_INDEXER_ID);
        $indexer->reindexAll();
        $this->eavConfig->clear();
        $setup->endSetup();
    }
}