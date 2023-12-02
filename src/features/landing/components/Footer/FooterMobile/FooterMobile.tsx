import { Box, Container } from '@mui/material';

import CustomLink from '@/components/ui/CustomLink/CustomLink';

import VilnaMainIcon from '../../Icons/VilnaMainIcon/VilnaMainIcon';
import FooterConfidential from '../FooterConfidential/FooterConfidential';
import FooterCopyright from '../FooterCopyright/FooterCopyright';
import FooterEmail from '../FooterEmail/FooterEmail';
import FooterSocials from '../FooterSocials/FooterSocials';

const styles = {
  logo: {
    width: '130px',
    justifySelf: 'flex-start',
    textDecoration: 'none',
    color: 'black',
  },
};

export default function FooterMobile() {
  return (
    <Box
      sx={{
        padding: '21px 15px 20px 15px',
      }}
    >
      <Container
        sx={{
          backgroundColor: '#fff',
          padding: '0',
          marginBottom: '12px',
        }}
      >
        <Box sx={{ display: 'flex', justifyContent: 'space-between', marginBottom: '15px' }}>
          <CustomLink href="/" style={{ ...styles.logo }}>
            <VilnaMainIcon />
          </CustomLink>
          <FooterSocials />
        </Box>

        <Box style={{ width: '100%', display: 'flex', justifyContent: 'center' }}>
          <FooterEmail
            style={{
              width: '100%',
              fontSize: '18px',
              fontWeight: '600',
              padding: '15px 10px 16px 10px',
            }}
          />
        </Box>

        <FooterConfidential style={{ marginTop: '4px', gap: '4px' }} />

        <FooterCopyright style={{ display: 'flex', justifyContent: 'center', marginTop: '16px' }} />
      </Container>
    </Box>
  );
}
