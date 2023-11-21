import { Box } from '@mui/material';
import { useTranslation } from 'react-i18next';

import CustomLink from '@/components/ui/CustomLink/CustomLink';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';

const styles = {
  confidentialsBox: {
    display: 'flex',
    gap: '8px',
  },
  confidentialsBoxMobileOrLower: {
    flexDirection: 'column',
    alignItems: 'stretch',
  },
  confidentialLink: {
    borderRadius: '8px',
    background: '#F4F5F6',
    color: '#969B9D',
    fontFamily: 'Inter-Regular, sans-serif',
    fontSize: '16px',
    fontStyle: 'normal',
    fontWeight: '500',
    lineHeight: '18px',
    padding: '8px 16px',
    display: 'flex',
    justifyContent: 'center',
  },
};

export default function FooterConfidential({ style }: { style?: React.CSSProperties }) {
  const { t } = useTranslation();
  const { isMobile, isSmallest } = useScreenSize();

  return (
    <Box sx={{
      ...styles.confidentialsBox,
      ...((isMobile || isSmallest) ? styles.confidentialsBoxMobileOrLower : {}),
      ...style,
    }}>
      <CustomLink href='/' style={{ ...styles.confidentialLink }}>
        {t('Privacy policy')}
      </CustomLink>
      <CustomLink href='/' style={{ ...styles.confidentialLink }}>
        {t('Usage policy')}
      </CustomLink>
    </Box>
  );
}

FooterConfidential.defaultProps = {
  style: {},
};
