import { Typography } from '@material-ui/core';
import { useTranslation } from 'react-i18next';

const styles = {
  copyrightText: {
    color: '#404142',
    fontFamily: 'GolosText-Regular, sans-serif',
    fontSize: '15px',
    fontStyle: 'normal',
    fontWeight: '500',
    lineHeight: '18px',
    alignSelf: 'center',
  },
};

export default function FooterCopyright({ style }: { style?: React.CSSProperties }) {
  const { t } = useTranslation();

  return (
    <Typography style={{ ...styles.copyrightText, ...style }}>
      {t(`Copyright © ТОВ “Вільна СРМ”, 2023`)}
    </Typography>
  );
}

FooterCopyright.defaultProps = {
  style: {},
};
