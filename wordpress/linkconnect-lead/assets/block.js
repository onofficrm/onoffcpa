(function (wp) {
  if (!wp || !wp.blocks || !wp.element || !wp.blockEditor) {
    return;
  }

  var el = wp.element.createElement;
  var registerBlockType = wp.blocks.registerBlockType;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var useBlockProps = wp.blockEditor.useBlockProps;
  var PanelBody = wp.components.PanelBody;
  var TextControl = wp.components.TextControl;
  var ServerSideRender = wp.serverSideRender
    ? wp.serverSideRender
    : wp.components && wp.components.ServerSideRender
      ? wp.components.ServerSideRender
      : null;
  var __ = wp.i18n.__;

  registerBlockType('linkconnect/lead-form', {
    edit: function (props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes;
      var blockProps = useBlockProps
        ? useBlockProps({ className: 'linkconnect-lead-block-editor' })
        : { className: 'linkconnect-lead-block-editor' };

      return el(
        'div',
        blockProps,
        el(
          InspectorControls,
          null,
          el(
            PanelBody,
            { title: __('온오프CPA 설정', 'linkconnect-lead'), initialOpen: true },
            el(TextControl, {
              label: __('홍보코드 (lkCode)', 'linkconnect-lead'),
              value: attributes.lkCode || '',
              help: __('비우면 플러그인 설정값을 사용합니다.', 'linkconnect-lead'),
              onChange: function (value) {
                setAttributes({ lkCode: value });
              },
            }),
            el(TextControl, {
              label: __('채널명 (선택)', 'linkconnect-lead'),
              value: attributes.channel || '',
              onChange: function (value) {
                setAttributes({ channel: value });
              },
            }),
            el(TextControl, {
              label: __('링크이름 / sub_id (선택)', 'linkconnect-lead'),
              value: attributes.subId || '',
              onChange: function (value) {
                setAttributes({ subId: value });
              },
            })
          )
        ),
        ServerSideRender
          ? el(ServerSideRender, {
              block: 'linkconnect/lead-form',
              attributes: attributes,
            })
          : el(
              'div',
              {
                style: {
                  border: '1px dashed #94a3b8',
                  borderRadius: '12px',
                  padding: '16px',
                  color: '#475569',
                  fontSize: '14px',
                },
              },
              __('온오프CPA 상담폼 (미리보기는 저장 후 확인)', 'linkconnect-lead')
            )
      );
    },
    save: function () {
      return null;
    },
  });
})(window.wp);
