import { Typography } from '@material-ui/core';
import { useTranslation } from 'react-i18next';
import { TRANSLATION_NAMESPACE } from '@/features/landing/utils/constants/constants';

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
  const { t } = useTranslation(TRANSLATION_NAMESPACE);

  return (
    <Typography style={{ ...styles.copyrightText, ...style }}>
      {t(`footer.copyright`)}
    </Typography>
  );
}

FooterCopyright.defaultProps = {
  style: {},
};
