import { Box } from '@mui/material';
import React from 'react';

import FooterBottom from '@/features/landing/components/Footer/FooterBottom/FooterBottom';
import FooterCopyright from '@/features/landing/components/Footer/FooterCopyright/FooterCopyright';
import FooterEmail from '@/features/landing/components/Footer/FooterEmail/FooterEmail';
import FooterHead from '@/features/landing/components/Footer/FooterHead/FooterHead';
import FooterMobile from '@/features/landing/components/Footer/FooterMobile/FooterMobile';
import FooterSocials from '@/features/landing/components/Footer/FooterSocials/FooterSocials';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';

export default function Footer() {
  const { isSmallest, isMobile, isTablet } = useScreenSize();

  if (isSmallest || isMobile) {
    return <FooterMobile />;
  }

  return (
    <footer style={{
      paddingLeft: (isSmallest || isMobile || isTablet) ? '5px' : '0',
      paddingRight: (isSmallest || isMobile || isTablet) ? '5px' : '0',
    }}>
      <FooterHead />
      <FooterBottom>
        <FooterCopyright />
        <Box sx={{ display: 'flex', alignItems: 'center', gap: '16px' }}>
          <FooterEmail />
          <FooterSocials />
        </Box>
      </FooterBottom>
    </footer>
  );
}
