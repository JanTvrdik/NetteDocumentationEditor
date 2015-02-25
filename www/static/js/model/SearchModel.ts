/// <reference path="../deps.ts" />

module LiveTexyEditor
{
	enum BranchState
	{
		LOADED,
		LOADING
	}

	export class SearchModel extends EventEmitter
	{
		/** list of loaded branches */
		private branches: {
			[branch: string]: BranchState
		};

		constructor(private searchUrl: string)
		{
			super();
			this.branches = {};
		}

		loadBranch(branch: string): void
		{
			if (typeof this.branches[branch] === 'undefined') {
				this.branches[branch] = BranchState.LOADING;
				$.post(this.searchUrl, {tree: branch}, (payload) => {
					this.branches[branch] = BranchState.LOADED;
					this.trigger('search:results', [branch, payload.pages]);
				});
			}
		}
	}
}
