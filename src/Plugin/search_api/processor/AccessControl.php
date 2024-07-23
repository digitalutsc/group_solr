<?php

namespace  Drupal\group_solr\Plugin\search_api\processor;

use Drupal\islandora_group\Utilities;
use Drupal\group\Entity\GroupRelationship;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\group_solr\Plugin\search_api\processor\Property\AddAccessControlProperty;
use Drupal\Core\Access\AccessResult;
use Drupal\user\Entity\User;


/**
 * Adds the item's URL to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "group_access_control",
 *   label = @Translation("Group: Access Control"),
 *   description = @Translation("Adds a field which determine access control with Group."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class AccessControl extends ProcessorPluginBase {
    /**
     * {@inheritdoc}
     */
    public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
        $properties = [];

        if (!$datasource) {
            $definition = [
                'label' => $this->t(' Group: Access Control'),
                'description' => $this->t('Add a field to determine access control with Group'),
                'type' => 'string',
                'processor_id' => $this->getPluginId(),
            ];
            $properties['search_api_group_access_control'] = new AddAccessControlProperty($definition);
        }

        return $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function addFieldValues(ItemInterface $item) {
        $entity = $item->getOriginalObject()->getValue();
        $operation = "view";

        if (\Drupal::hasService('group_relation_type.manager')) {
            /** @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager */
            $plugin_manager = \Drupal::service('group_relation_type.manager');

            if (!method_exists($entity, "getEntityTypeId"))
            return;
            
            $plugin_ids = $plugin_manager->getPluginIdsByEntityTypeAccess($entity->getEntityTypeId());

            $plugin_cache_tags = [];
            foreach ($plugin_ids as $plugin_id) {
                $plugin_cache_tags[] = "group_content_list:plugin:$plugin_id";
            }

            // Load all of the group content for this entity.
            $group_contents = GroupRelationship::loadByEntity($entity);
            if (!empty($group_contents) && count($group_contents) > 0) {
                $access = AccessResult::neutral();
                foreach ($plugin_ids as $plugin_id) {
                    /*if (!$plugin_manager->hasHandler($plugin_id, 'access')) {
                        continue;
                    }*/

                    $handler = $plugin_manager->getAccessControlHandler($plugin_id);
                    $access = $access->orIf($handler->entityAccess($entity, $operation, User::getAnonymousUser(), TRUE));
                }

                $access
                    ->addCacheTags($plugin_cache_tags)
                    ->addCacheContexts(['user.group_permissions']);

                if ($access->isAllowed()) {
                    $value = "200";
                }
                else {
                    $groups = Utilities::getGroupsByNode($entity->id());
                    sort($groups);
                    $value = implode(",", $groups);
                }


            }else {
                $value = "200";
            }
        } else {
            $value = "200";
        }
        
        // index field
        $fields = $item->getFields(FALSE);
        $fields = $this->getFieldsHelper()
            ->filterForPropertyPath($fields, NULL, 'search_api_group_access_control');
        foreach ($fields as $field) {
            $field->addValue($value);
        }
    }

}
