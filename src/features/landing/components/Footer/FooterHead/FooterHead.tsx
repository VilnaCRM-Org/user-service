import { Box, Container } from '@mui/material';

import CustomLink from '@/components/ui/CustomLink/CustomLink';
import VilnaMainIcon from '@/features/landing/components/Icons/VilnaMainIcon/VilnaMainIcon';
import { useTranslation } from 'react-i18next';

const styles = {
  mainContainer: {
    width: '100%',
    maxWidth: '1192px',
    margin: '0 auto',
    padding: '11px 0px 7px 0px',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  logo: {
    width: '130px',
    justifySelf: 'flex-start',
    textDecoration: 'none',
    color: 'black',
  },
  confidentialsBox: {
    display: 'flex',
    gap: '8px',
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
  },
};

export default function FooterHead() {
  const { t } = useTranslation();

  return (
    <Container sx={{ ...styles.mainContainer }}>
      <CustomLink href="/" style={{ ...styles.logo }}>
        <VilnaMainIcon />
      </CustomLink>
      <Box sx={{ ...styles.confidentialsBox }}>
        <CustomLink href="/" style={{ ...styles.confidentialLink }}>
          {t('Privacy policy')}
        </CustomLink>
        <CustomLink href="/" style={{ ...styles.confidentialLink }}>
          {t('Usage policy')}
        </CustomLink>
      </Box>
    </Container>
  );
}
