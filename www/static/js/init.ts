/// <reference path="deps.ts" />

module LiveTexyEditor
{
	declare var processUrl: string;
	declare var controlId: string;

	$(() => {
		var container = $('.live-texy-editor');
		var diffRenderer = new DiffRenderer(300, 4);
		var model = new Model(diffRenderer, processUrl, controlId);
		var view = new EditorView(container, model);

		var backupAlert = localStorage.getItem('backupAlert');
		if (!backupAlert) {
			alert('You are responsible for backing up what you\'ve written, because I haven\'t implemented it yet. Your text may be lost at unexpected moments.');
			localStorage.setItem('backupAlert', 'true');
		}
	});
}

