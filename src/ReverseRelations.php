<?php

namespace codemonauts\reverserelations;

use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use yii\base\Event;
use codemonauts\reverserelations\fields\ReverseRelations as ReverseRelationsField;

class ReverseRelations extends Plugin
{
    public function init()
    {
        parent::init();

        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = ReverseRelationsField::class;
        });
    }
}
