<?php namespace App\Libraries;

use Request;
use stdClass;
use Session;
use App\Models\EntityModel;
use App\Models\Activity;

class HistoryUtils
{
    public static function loadHistory($users)
    {
        $userIds = [];

        if (is_array($users)) {
            foreach ($users as $user) {
                $userIds[] = $user->user_id;
            }
        } else {
            $userIds[] = $users;
        }

        $activityTypes = [
            ACTIVITY_TYPE_CREATE_CLIENT,
            ACTIVITY_TYPE_CREATE_TASK,
            ACTIVITY_TYPE_UPDATE_TASK,
            ACTIVITY_TYPE_CREATE_INVOICE,
            ACTIVITY_TYPE_UPDATE_INVOICE,
            ACTIVITY_TYPE_EMAIL_INVOICE,
            ACTIVITY_TYPE_CREATE_QUOTE,
            ACTIVITY_TYPE_UPDATE_QUOTE,
            ACTIVITY_TYPE_EMAIL_QUOTE,
            ACTIVITY_TYPE_VIEW_INVOICE,
            ACTIVITY_TYPE_VIEW_QUOTE,
        ];

        $activities = Activity::scope()
            ->with(['client.contacts', 'invoice', 'task'])
            ->whereIn('user_id', $userIds)
            ->whereIn('activity_type_id', $activityTypes)
            ->orderBy('id', 'asc')
            ->limit(100)
            ->get();

        foreach ($activities as $activity)
        {
            if ($activity->activity_type_id == ACTIVITY_TYPE_CREATE_CLIENT) {
                $entity = $activity->client;
            } else if ($activity->activity_type_id == ACTIVITY_TYPE_CREATE_TASK || $activity->activity_type_id == ACTIVITY_TYPE_UPDATE_TASK) {
                $entity = $activity->task;
                $entity->setRelation('client', $activity->client);
            } else {
                $entity = $activity->invoice;
                $entity->setRelation('client', $activity->client);
            }

            static::trackViewed($entity);
        }
    }

    public static function trackViewed(EntityModel $entity)
    {
        if ($entity->isEntityType(ENTITY_CREDIT)
                || $entity->isEntityType(ENTITY_PAYMENT)
                || $entity->isEntityType(ENTITY_VENDOR)) {
            return;
        }

        $object =  static::convertToObject($entity);
        $history = Session::get(RECENTLY_VIEWED) ?: [];
        $accountHistory = isset($history[$entity->account_id]) ? $history[$entity->account_id] : [];
        $data = [];

        // Add to the list and make sure to only show each item once
        for ($i = 0; $i<count($accountHistory); $i++) {
            $item = $accountHistory[$i];

            if ($object->url == $item->url) {
                continue;
            }

            array_push($data, $item);

            if (isset($counts[$item->accountId])) {
                $counts[$item->accountId]++;
            } else {
                $counts[$item->accountId] = 1;
            }
        }

        array_unshift($data, $object);

        if (isset($counts[$entity->account_id]) && $counts[$entity->account_id] > RECENTLY_VIEWED_LIMIT) {
            array_pop($data);
        }

        $history[$entity->account_id] = $data;

        Session::put(RECENTLY_VIEWED, $history);
    }

    private static function convertToObject($entity)
    {
        $object = new stdClass();
        $object->accountId = $entity->account_id;
        $object->url = $entity->present()->url;
        $object->entityType = $entity->subEntityType();
        $object->name = $entity->present()->titledName;
        $object->timestamp = time();

        if ($entity->isEntityType(ENTITY_CLIENT)) {
            $object->client_id = $entity->public_id;
            $object->client_name = $entity->getDisplayName();
        } elseif (method_exists($entity, 'client') && $entity->client) {
            $object->client_id = $entity->client->public_id;
            $object->client_name = $entity->client->getDisplayName();
        } else {
            $object->client_id = 0;
            $object->client_name = 0;
        }

        return $object;
    }

    public static function renderHtml($accountId)
    {
        $lastClientId = false;
        $clientMap = [];
        $str = '';

        $history = Session::get(RECENTLY_VIEWED, []);
        $history = isset($history[$accountId]) ? $history[$accountId] : [];

        foreach ($history as $item)
        {
            if ($item->entityType == ENTITY_CLIENT && isset($clientMap[$item->client_id])) {
                continue;
            }

            $clientMap[$item->client_id] = true;

            if ($lastClientId === false || $item->client_id != $lastClientId)
            {
                $icon = '<i class="fa fa-users" style="width:32px"></i>';
                if ($item->client_id) {
                    $link = url('/clients/' . $item->client_id);
                    $name = $item->client_name ;

                    $buttonLink = url('/invoices/create/' . $item->client_id);
                    $button = '<a type="button" class="btn btn-primary btn-sm pull-right" href="' . $buttonLink . '">
                                    <i class="fa fa-plus-circle" style="width:20px" title="' . trans('texts.create_invoice') . '"></i>
                                </a>';
                } else {
                    $link = '#';
                    $name = trans('texts.unassigned');
                    $button = '';
                }

                $str .= sprintf('<li>%s<a href="%s"><div>%s %s</div></a></li>', $button, $link, $icon, $name);
                $lastClientId = $item->client_id;
            }

            if ($item->entityType == ENTITY_CLIENT) {
                continue;
            }

            $icon = '<i class="fa fa-' . EntityModel::getIcon($item->entityType . 's') . '" style="width:24px"></i>';
            $str .= sprintf('<li style="text-align:right; padding-right:18px;"><a href="%s">%s %s</a></li>', $item->url, $item->name, $icon);
        }

        return $str;
    }
}
