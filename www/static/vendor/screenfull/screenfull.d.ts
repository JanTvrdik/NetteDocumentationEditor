interface Screnfull
{
	request(elem: HTMLElement): void;
	exit(): void;
	toggle(elem: HTMLElement): void;
	onchange(): void;
	onerror(): void;

	isFullscreen: boolean;
	element: HTMLElement;
	enabled: boolean;
	raw: any;
}

declare var screenfull: Screnfull;
