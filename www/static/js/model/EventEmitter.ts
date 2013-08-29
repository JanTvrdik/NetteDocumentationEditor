/// <reference path="../deps.ts" />

module LiveTexyEditor
{
	export class EventEmitter
	{
		/** list of registered event listeners */
		private listeners: {
			[eventName: string]: Function[];
		};

		constructor()
		{
			this.listeners = {};
		}

		on(eventName: string, callback: Function)
		{
			var events = eventName.split(' ');
			for (var i = 0; i < events.length; i++) {
				var event = events[i];
				if (typeof this.listeners[event] === 'undefined') this.listeners[event] = [];
				this.listeners[event].push(callback);
			}
		}

		trigger(eventName: string, args: any[] = [])
		{
			if (eventName in this.listeners) {
				for (var i = 0; i < this.listeners[eventName].length; i++) {
					this.listeners[eventName][i].apply(this, args);
				}
			}
		}
	}
}
