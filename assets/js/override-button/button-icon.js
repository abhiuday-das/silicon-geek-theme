// WordPress Button Icon Enhancement
(function (wp) {
  'use strict';

  if (!wp) return;

  const { addFilter } = wp.hooks;
  const { createHigherOrderComponent } = wp.compose;
  const { InspectorControls } = wp.blockEditor;
  const { PanelBody, ToggleControl } = wp.components;
  const { Fragment, createElement } = wp.element;

  // 1. Add custom attribute to core/button
  function addButtonAttribute(settings, name) {
    if (name !== 'core/button') return settings;

    return {
      ...settings,
      attributes: {
        ...settings.attributes,
        withIcon: {
          type: 'boolean',
          default: true
        }
      }
    };
  }

  // 2. Add toggle control in sidebar
  const withIconToggle = createHigherOrderComponent(function (BlockEdit) {
    return function (props) {
      if (props.name !== 'core/button') {
        return createElement(BlockEdit, props);
      }

      const { attributes, setAttributes } = props;
      const { withIcon } = attributes;

      return createElement(
        Fragment,
        {},
        createElement(BlockEdit, props),
        createElement(
          InspectorControls,
          {},
          createElement(
            PanelBody,
            {
              title: 'Icon Settings',
              initialOpen: true
            },
            createElement(ToggleControl, {
              label: 'Enable Icon Support',
              help: 'Allow icons to be added to this button',
              checked: withIcon,
              onChange: function (value) {
                setAttributes({ withIcon: value });
              }
            })
          )
        )
      );
    };
  }, 'withIconToggle');

  // 3. Add class to button wrapper
  function addIconClass(extraProps, blockType, attributes) {
    if (blockType.name === 'core/button' && attributes.withIcon) {
      const className = extraProps.className || '';
      extraProps.className = className + ' is-style-with-icon';
    }
    return extraProps;
  }

  // 4. Apply to editor view as well
  function addEditorClass(BlockListBlock) {
    return function (props) {
      if (props.name === 'core/button' && props.attributes.withIcon) {
        const wrapperProps = props.wrapperProps || {};
        wrapperProps.className = (wrapperProps.className || '') + ' is-style-with-icon';
        return createElement(BlockListBlock, {
          ...props,
          wrapperProps: wrapperProps
        });
      }
      return createElement(BlockListBlock, props);
    };
  }
  
  // Register all filters
  addFilter('blocks.registerBlockType', 'mytheme/button-icon-attribute', addButtonAttribute);
  addFilter('editor.BlockEdit', 'mytheme/button-icon-toggle', withIconToggle);
  addFilter('blocks.getSaveContent.extraProps', 'mytheme/button-icon-class', addIconClass);
  addFilter('editor.BlockListBlock', 'mytheme/button-icon-editor-class', addEditorClass);

})(window.wp);
