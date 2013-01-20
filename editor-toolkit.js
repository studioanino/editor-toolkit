/**
 * Editor Toolkit: TinyMCE plugin
 */

(function($) {
    tinymce.create('tinymce.plugins.editor_toolkit', {
        init : function(ed, url) {
			var VK = {
				DELETE: 46, BACKSPACE: 8, ENTER: 13, TAB: 9, SPACEBAR: 32, UP: 38, DOWN: 40, LEFT: 37, RIGHT: 39,
				modifierPressed: function (e) {
					return e.shiftKey || e.ctrlKey || e.altKey;
				}
			};
			function scrollToThisNode(node, padding) {
				vp = ed.dom.getViewPort( ed.getWin() )
				y = ed.dom.getPos(node).y;
				if (y < vp.y || y + padding > vp.y + vp.h) {
					ed.getWin().scrollTo(0, y < vp.y ? y : y - vp.h + padding);
				}
			}
			ed.onInit.add(function(ed) {
				ed.dom.addClass(ed.getBody(), 'editor-toolkit');
				
				// Last-ditch effort to structure content
				$('#post').submit(function(){
					$( ed.getBody() ).children('p').each(function(){
						$(this).replaceWith('<div><p>' + $(this).html() + '</p></div>');
					});
					return true;
				});
				
			});
			ed.onBeforeSetContent.add(function(ed, o) {
				if (adminpage == 'post-new-php' && !$( ed.getBody() ).hasClass('editor-toolkit-init') ) {
					o.content = '<div><p><br /></p>';
					ed.dom.addClass(ed.getBody(), 'editor-toolkit-init');
				}
			});
			ed.onKeyDown.add(function(ed, event) {
				if ( event.keyCode === VK.DELETE && !VK.modifierPressed(event) ) {
					
				}				
				if ( event.keyCode === VK.BACKSPACE && !VK.modifierPressed(event) ) {
					
				}				
				if ( event.keyCode === VK.ENTER && !VK.modifierPressed(event) ) {
					setTimeout(function() {
						var currentNode = ed.dom.getParent(ed.selection.getRng().startContainer, ed.dom.isBlock);
						var prevSibling = $(currentNode).length > 0 ? currentNode.previousSibling : false;
						var nextSibling = $(currentNode).length > 0 ? currentNode.nextSibling : false;
						var prevPrevSibling = $(prevSibling).length > 0 ? prevSibling.previousSibling : false;
						var nodeIndex = $(currentNode).length > 0 ? $(currentNode).index() : false;
						var nodeIsLast = $(currentNode).length > 0 ? $(currentNode).is(':last-child') : null;

						var nodeParent = ed.dom.getParent(currentNode, 'div');
						var nodeParentPrevSibling = $(nodeParent).length > 0 ? nodeParent.previousSibling : false;
						var nodeParentNextSibling = $(nodeParent).length > 0 ? nodeParent.nextSibling : false;
						var nodeParentChildren = $(nodeParent).length > 0 ? $(nodeParent).children().length : false;
						
						var contentBody = ed.getBody();
						var contentFirstChild = ed.getBody().firstChild;

						var newNodeData = '<p><br /></p>';

						function isEmpty(node) {
							if (ed.dom.isEmpty(node) || node.firstChild.nodeName === 'BR' || $(node).html() === '&nbsp;')
								return true;
							else
								return false;
						}
						
						switch(true) {
							// Start new block at selection point ("after")
							case nodeIsLast && nodeIndex + 1 === nodeParentChildren && nodeParentChildren > 3:
							case nodeIndex === 2 && nodeParentChildren === 3 && ( !isEmpty(prevSibling) || !isEmpty(prevPrevSibling) ):
								if ( isEmpty(prevSibling) && isEmpty(currentNode) ) {
									$(nodeParent).after('<div>' + newNodeData + '</div>');
									ed.dom.remove(prevSibling);
									ed.dom.remove(currentNode);
									newBlock = nodeParent.nextSibling;
									ed.execCommand('mceSelectNode', false, newBlock.firstChild.firstChild);
									ed.selection.select(newBlock.firstChild.firstChild, true);  // Extra to get FF to show cursor
									scrollToThisNode(newBlock, 100);
								}
								break;
							// Start new block at selection point ("before")
							case nodeIndex === 2:
								if ( isEmpty(prevSibling) && isEmpty(prevPrevSibling) ) {
									$(nodeParent).before('<div>' + newNodeData + '</div>');
									ed.dom.remove(prevSibling);
									ed.dom.remove(prevPrevSibling);
									newBlock = nodeParent.previousSibling;
									if ( $(newBlock).is(':first-child') ) {
										ed.execCommand('mceSelectNode', false, newBlock.firstChild.firstChild);
										ed.selection.select(newBlock.firstChild.firstChild, true);  // Extra to get FF to show cursor
										scrollToThisNode(newBlock, 100);
									}
								}
								break;
							// Correct new paragraph created outside a block
							case currentNode && currentNode.nodeName === 'P' && !nodeParent:
								newNodeData = $(currentNode).html();
								$(currentNode).after('<div><p>' + newNodeData + '</p></div>');
								newBlock = currentNode.nextSibling;
								ed.dom.remove(currentNode);
								break;
							// Rare but best to cover all angles (problematic on empty pages)
							case contentFirstChild.nodeName === 'P':
								$(contentFirstChild).replaceWith('<div>' + newNodeData + '</div>');
								newBlock = ed.getBody().firstChild;
								ed.execCommand('mceSelectNode', false, newBlock.firstChild.firstChild);
								ed.selection.select(newBlock.firstChild.firstChild, true);  // Extra to get FF to show cursor
								break;
							// Fix empty block
							case ed.dom.is(ed.selection.getNode(), 'div') && isEmpty(currentNode):
								ed.execCommand('mceReplaceContent', false, '<p></p>');
								break;
						}
						
						// Run a check for paragraphs outside a block, at root level
						setTimeout(function() {
							$(contentBody).children('p').each(function(){
								$(this).replaceWith('<div><p>' + $(this).html() + '</p></div>');
							});
						}, 250);
					
						ed.execCommand('mceCleanup');
					}, 0);
				}
			});
        },
        createControl : function(n, cm) {
            return null;
        },
        getInfo : function() {
            return {
                longname : "Editor Toolkit",
                author : 'Studio Anino',
                authorurl : 'http://studioanino.com/',
                infourl : 'http://studioanino.com/',
                version : "1.0"
            };
        }
    });
    tinymce.PluginManager.add('editor_toolkit', tinymce.plugins.editor_toolkit);
})(jQuery);
