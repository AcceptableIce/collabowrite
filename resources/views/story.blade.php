@extends('app')

@section('script')
	<script type="text/javascript">
	function storyVM() {
		var self = this;
		self.storyTree = ko.observable(<?php echo json_encode($story->buildTree()) ?>);
		self.editingTier = ko.observable(-9);
		self.editingValue = ko.observable("");
		self.editingParentId = ko.observable(-1);
		//Modify the story tree to have observables
	
		function modifyStoryTree(root) {
			root.selected = ko.observable(root.selected);
			for(var i = 0; i < root.children.length; i++) {
				var child = root.children[i];
				modifyStoryTree(child);
			}
		}	
		modifyStoryTree(self.storyTree());
		
		self.buildPath = ko.computed(function() {
			var out = [];
			var root = self.storyTree();
			while(root.children[root.selected()] != null) {
				out.push(root);
				root = root.children[root.selected()];	
			}
			
			out.push(root);

			return out;
		});
		
		self.shiftTier = function(index, value) {
			//Iterate through until we get to where we want.
			var tier = self.goToTier(index - 1);
			tier.selected(tier.selected() + value);
			if(tier.selected() < 0) tier.selected(tier.children.length - 1);
			if(tier.selected() > tier.children.length - 1) tier.selected(0);
			self.cancelSentenceInput();
		}
		
		self.goToTier = function(value) {
			var tier = self.storyTree();
			for(var i = 0; i < value; i++) {
				tier = tier.children[tier.selected()];
			}
			return tier;
		}
		
		self.startEditing = function(tier, id) {
			self.editingValue("");
			self.editingParentId(id);
			self.editingTier(tier);
		}
		
		self.cancelSentenceInput = function() {
			self.editingValue("");
			self.editingParentId(-1);
			self.editingTier(-9);
		}
		
		self.submitSentenceInput = function() {
			$.ajax('/api/v1/story/{{$story->id}}/reply', {
					data: {
							"sentence_id": self.editingParentId(),
							"reply": self.editingValue()
						},
					method: 'POST',
					success: function(data) {
						console.log('Reply submitted!');
						var tier = self.goToTier(self.editingTier());
						tier.children.push({ content: self.editingValue(), selected: ko.observable(0), id: data.id, children: [] });
						tier.selected(tier.children.length - 1);
						self.cancelSentenceInput();
						self.storyTree.valueHasMutated();

					},
					error: function(jqXHR, textStatus, errorThrown) {
						console.log('Submit error', jqXHR);	
					}
				});
		}
		
		return self;
		//Build the tree.
	}
	
	ko.applyBindings(new storyVM());
	</script>
@endsection

@section('content')
	<div class="author-block">This is a story that was started {{$story->created_at->diffForHumans()}} by {{$story->owner()->name}}.</div>
	<div class="story-block">
		<!-- ko foreach: buildPath -->
		<div class="story-tier" data-bind="css: {'tier-0': $index() == 0}">
			<div class="container">
				<div class="row" data-bind="visible: $root.editingTier() + 1 != $index()">
					<div class="story-sentence" data-bind="text: $data.content"></div>
					<!-- ko if: $index() > 0 -->
					<div class="button tier-button tier-shift-left" data-bind="click: function() { $root.shiftTier($index(), -1) }, css: {'disabled': $root.buildPath()[$index() - 1].children.length < 2}">&#8592;</div>
					<!-- /ko -->
					
					<div class="button tier-button reply-to-tier" data-bind="click: function() { $root.startEditing($index(), $data.id) }">Reply</div>
					
					<!-- ko if: $index() > 0 -->
						<div class="button tier-button tier-shift-right"  data-bind="click: function() { $root.shiftTier($index(), 1) }, css: {'disabled': $root.buildPath()[$index() - 1].children.length < 2}">&#8594;</div>
					<!-- /ko -->
				</div>
				<div class="row edit-row" data-bind="visible: $root.editingTier() + 1 == $index()">
					<div class="sentence-input-copy">Write a reply to the line above.</div>
					<input type="text" class="sentence-input" data-bind="value: $root.editingValue" />
					<div class="button tier-button cancel-sentence-submit" data-bind="click: $root.cancelSentenceInput">Cancel</div>
					<div class="button tier-button confirm-sentence-submit" data-bind="click: $root.submitSentenceInput">Submit</div>

				</div>
			</div>
		</div>
		<!-- /ko -->
		<div class="story-tier last-tier">
			<div class="container">
				<div class="row edit-row" data-bind="visible: $root.editingTier() + 1 == $root.buildPath().length">
					<div class="sentence-input-copy">Write a reply to the line above.</div>
					<input type="text" class="sentence-input" data-bind="value: $root.editingValue" />
					<div class="button tier-button cancel-sentence-submit" data-bind="click: $root.cancelSentenceInput">Cancel</div>
					<div class="button tier-button confirm-sentence-submit" data-bind="click: $root.submitSentenceInput">Submit</div>
				</div>
			</div>
		</div>
	</div>
@endsection
