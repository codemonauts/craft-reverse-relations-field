<?php

namespace codemonauts\reverserelations\fields;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\db\Table;
use craft\elements\db\ElementQuery;
use craft\fields\Entries;
use craft\helpers\Db;

class ReverseRelations extends Entries
{
    /**
     * @var int The target field ID.
     */
    public $targetFieldId;

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        $this->allowLimit = false;
        $this->sortable = false;
    }

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('reverserelations', 'Reverse Relations Field');
    }

    /**
     * {@inheritdoc}
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        /** @var Element|null $element */
        $query = parent::normalizeValue($value, $element);

        // Overwrite inner join to switch sourceId and targetId
        if (!is_array($value) && $value !== '' && $element && $element->id) {
            $targetField = Craft::$app->fields->getFieldByUid($this->targetFieldId);

            $query->join = [];
            $query->innerJoin('{{%relations}} relations', [
                'and',
                '[[relations.sourceId]] = [[elements.id]]',
                [
                    'relations.targetId' => $element->id,
                    'relations.fieldId' => $targetField->id,
                ],
                [
                    'or',
                    ['relations.sourceSiteId' => null],
                    ['relations.sourceSiteId' => $element->siteId],
                ],
            ]);

            $inputSourceIds = $this->inputSourceIds();
            if ($inputSourceIds !== '*') {
                $query->where(['entries.sectionId' => $inputSourceIds]);
            }
        }

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'targetFieldId';

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsHtml(): ?string
    {
        $settings = parent::getSettingsHtml();

        $fieldSelectTemplate = Craft::$app->view->renderTemplate('reverserelations/_settings',[
            'fields' => $this->getFields(),
            'settings' => $this->getSettings(),
        ]);

        return $settings.$fieldSelectTemplate;
    }

    /**
     * Get available fields.
     *
     * @return array
     */
    protected function getFields(): array
    {
        $fields = [];
        /** @var Field $field */
        foreach (Craft::$app->fields->getAllFields(false) as $field) {
            if ($field instanceof Entries && !($field instanceof $this)) {
                $fields[$field->uid] = $field->name;
            }
        }

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        /** @var Element|null $element */
        if ($element !== null && $element->hasEagerLoadedElements($this->handle)) {
            $value = $element->getEagerLoadedElements($this->handle);
        }

        /** @var ElementQuery|array $value */
        $variables = $this->inputTemplateVariables($value, $element);

        return Craft::$app->view->renderTemplate('reverserelations/_input', $variables);
    }

    /**
     * Get input source ids.
     *
     * @return array|string
     */
    private function inputSourceIds()
    {
        $inputSources = $this->inputSources();

        if ($inputSources === '*') {
            return $inputSources;
        }

        $sources = [];
        foreach ($inputSources as $source) {
            [, $uid] = explode(':', $source);
            $sources[] = $uid;
        }

        return Db::idsByUids(Table::SECTIONS, $sources);
    }
}
