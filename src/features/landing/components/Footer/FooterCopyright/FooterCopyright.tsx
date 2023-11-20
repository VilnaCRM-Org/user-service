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
  },
};

export default function FooterCopyright() {
  const { t } = useTranslation();

  return (
    <Typography style={{ ...styles.copyrightText }}>
      {t(`Copyright © ТОВ “Вільна СРМ”, 2023`)}
    </Typography>
  );
}
