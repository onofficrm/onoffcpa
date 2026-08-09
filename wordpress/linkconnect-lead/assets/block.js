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
  var SelectControl = wp.components.SelectControl;
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
            { title: __('LinkConnect 설정', 'linkconnect-lead'), initialOpen: true },
            el(TextControl, {
              label: __('홍보코드 (lkCode)', 'linkconnect-lead'),
              value: attributes.lkCode || '',
              help: __('비우면 플러그인 설정값을 사용합니다.', 'linkconnect-lead'),
              onChange: function (value) {
                setAttributes({ lkCode: value });
              },
            }),
            el(TextControl, {
              label: __('위젯 키 (권장)', 'linkconnect-lead'),
              value: attributes.widgetKey || '',
              help: __('파트너센터에서 발급한 wgt_ 키. 발급된 경우 입력하세요.', 'linkconnect-lead'),
              onChange: function (value) {
                setAttributes({ widgetKey: value });
              },
            }),
            el(SelectControl, {
              label: __('위젯 형태', 'linkconnect-lead'),
              value: attributes.mode || '',
              options: [
                { label: __('설정값 사용', 'linkconnect-lead'), value: '' },
                { label: __('폼형', 'linkconnect-lead'), value: 'form' },
                { label: __('버튼형', 'linkconnect-lead'), value: 'button' },
                { label: __('전화형', 'linkconnect-lead'), value: 'phone' },
              ],
              onChange: function (value) {
                setAttributes({ mode: value });
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
              __('LinkConnect 상담폼 (미리보기는 저장 후 확인)', 'linkconnect-lead')
            )
      );
    },
    save: function () {
      return null;
    },
  });
})(window.wp);
