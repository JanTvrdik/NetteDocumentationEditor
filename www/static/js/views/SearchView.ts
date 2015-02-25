/// <reference path="../deps.ts" />

module LiveTexyEditor
{
	enum QueryType
	{
		BRANCH,
		PAGE
	}


	class Query
	{
		words: string[];
		type: QueryType;

		constructor(public str: string)
		{
			this.words = str.toLowerCase().trim().split(/[\s:]+/);
			this.type = (this.words.length === 1 ? QueryType.BRANCH : QueryType.PAGE);
		}

		isSubQueryOf(query: Query)
		{
			return this.type === query.type && this.str.lastIndexOf(query.str) === 0;
		}
	}


	class LinkedListNode<T>
	{
		prev: LinkedListNode<T>;
		next: LinkedListNode<T>;
		value: T;

		constructor(value: T)
		{
			this.prev = this.next = null;
			this.value = value;
		}
	}


	class LinkedList<T>
	{
		first: LinkedListNode<T>;
		last: LinkedListNode<T>;

		constructor()
		{
			this.clear();
		}

		findByValue(value: T): LinkedListNode<T>
		{
			for (var node = this.first; node; node = node.next) {
				if (node.value === value) {
					return node;
				}
			}
			return null;
		}

		appendValue(value: T): LinkedListNode<T>
		{
			var node = new LinkedListNode(value);
			if (this.first === null) {
				this.first = this.last = node;

			} else {
				node.prev = this.last;
				this.last.next = node;
				this.last = node;
			}
			return node;
		}

		removeNode(node: LinkedListNode<T>): void
		{
			if (node != this.first) node.prev.next = node.next;
			else this.first = node.next;

			if (node != this.last) node.next.prev = node.prev;
			else this.last = node.prev;
		}

		clear(): void
		{
			this.first = this.last = null;
		}
	}


	export class SearchView
	{
		private popup: JQuery;
		private items: HTMLLIElement[];
		private visible: LinkedList<HTMLLIElement>;
		private active: LinkedListNode<HTMLLIElement>;
		private prevQuery: Query;

		constructor(private container: JQuery, private searchModel: SearchModel)
		{
			this.initElements();
			this.initEvents();
		}

		private initElements()
		{
			this.popup = this.container.find('.search');
			this.items = this.popup.find('li').toArray();
			this.visible = new LinkedList<HTMLLIElement>();
			this.active = null;
			this.prevQuery = new Query('');
		}

		private checkMatch(query: Query, item: HTMLLIElement): boolean
		{
			var text = item.textContent.toLowerCase();
			var isBranch = item.classList.contains('branch');
			var shouldBeBranch = (query.type === QueryType.BRANCH);
			return shouldBeBranch === isBranch && query.words.every((word) => text.indexOf(word) >= 0);
		}

		private search(query: Query, forceFullScan: boolean = false)
		{
			if (query.words.length > 1) {
				this.searchModel.loadBranch(query.words[0]);
			}

			if (!forceFullScan && query.isSubQueryOf(this.prevQuery)) {
				for (var node = this.visible.first; node; node = node.next) {
					if (!this.checkMatch(query, node.value)) {
						if (node === this.active) {
							this.setActive(node.next || node.prev);
						}
						node.value.style.display = 'none';
						this.visible.removeNode(node);
					}
				}

			} else {
				this.visible.clear();
				this.items.forEach((item) => {
					if (this.checkMatch(query, item)) {
						var node = this.visible.appendValue(item);
						item.style.display = 'block';

					} else {
						var node = <LinkedListNode<HTMLLIElement>> null;
						item.style.display = 'none';
					}

					if (this.active !== null && this.active.value === item) {
						this.setActive(node);
					}
				});

				if (this.active === null) {
					this.setActive(this.visible.first);
				}
			}

			this.prevQuery = query;
		}

		private initEvents()
		{
			$(window).on('keydown keypress', (e: JQueryEventObject) => {
				var E_KEY_CODE = 69;
				if (e.keyCode === E_KEY_CODE && e.ctrlKey && !e.altKey && !e.shiftKey) {
					e.preventDefault();
					this.popup.show();
					this.popup.find('input').focus();
					this.search(this.prevQuery, true);
				}
			});

			this.container.on('click', (e: JQueryEventObject) => {
				this.popup.hide();
			});

			this.popup.on('click', (e: JQueryEventObject) => {
				e.stopPropagation();
			});

			this.popup.on('click', 'li', (e: JQueryEventObject) => {
				var item = <HTMLLIElement> e.target;
				this.setActive(this.visible.findByValue(item));
				this.handleActiveConfirmed();
			});

			this.popup.find('input').on('input', (e: JQueryKeyEventObject) => {
				var input = <HTMLInputElement> e.target;
				this.search(new Query(input.value));
			});

			this.popup.on('keydown', (e: JQueryKeyEventObject) => {
				var ENTER_KEY_CODE = 13;
				var ESC_KEY_CODE = 27;
				var ARROW_UP_KEY_CODE = 38;
				var ARROW_DOWN_KEY_CODE = 40;

				if (e.keyCode === ARROW_UP_KEY_CODE || e.keyCode === ARROW_DOWN_KEY_CODE) {
					e.preventDefault();
					if (this.active !== null) {
						this.setActive(e.keyCode === ARROW_DOWN_KEY_CODE
							? this.active.next || this.visible.first
							: this.active.prev || this.visible.last
						);
					}

				} else if (e.keyCode === ENTER_KEY_CODE) {
					e.preventDefault();
					if (this.active !== null) {
						this.handleActiveConfirmed();
					}

				} else if (e.keyCode === ESC_KEY_CODE) {
					e.preventDefault();
					this.popup.hide();
				}
			});

			this.searchModel.on('search:results', (branch: string, pages: string[]) => {
				var ul = this.popup.find('ul').get(0);
				pages.forEach((page) => {
					var li = document.createElement('li');
					li.textContent = branch + ":" + page;
					ul.appendChild(li);
					this.items.push(li);
				});
				this.search(this.prevQuery, true);
			});
		}

		private setActive(node: LinkedListNode<HTMLLIElement>)
		{
			if (this.active !== null) {
				this.active.value.classList.remove('active');
			}

			this.active = node;

			if (this.active !== null) {
				this.active.value.classList.add('active');

				var item = this.active.value;
				var list = <HTMLUListElement> item.parentElement;
				var margin = 5;

				if (item.offsetTop + item.clientHeight + margin > list.scrollTop + list.clientHeight) {
					list.scrollTop = item.offsetTop + item.clientHeight + margin - list.clientHeight;

				} else if (item.offsetTop - margin < list.scrollTop) {
					list.scrollTop = item.offsetTop - margin;
				}
			}
		}

		private handleActiveConfirmed()
		{
			if (this.active.value.classList.contains('branch')) {
				this.popup.find('input').val(this.active.value.textContent + ':').trigger('input');

			} else {
				this.container.find('input[name=page]').val(this.active.value.textContent);
				this.container.find('input[name=open]').click();
			}
		}
	}
}
