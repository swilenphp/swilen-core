<?php

namespace Swilen\Events;

interface Listener
{
    public function handle(Event $event): void;
}

interface ListenerProvider
{
    public function getListenersForEvent(Event $event): iterable;
}
