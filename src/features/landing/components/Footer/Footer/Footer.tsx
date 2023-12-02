import { Box } from '@mui/material';
import React from 'react';

import useScreenSize from '../../../hooks/useScreenSize/useScreenSize';
import FooterBottom from '../FooterBottom/FooterBottom';
import FooterCopyright from '../FooterCopyright/FooterCopyright';
import FooterEmail from '../FooterEmail/FooterEmail';
import FooterHead from '../FooterHead/FooterHead';
import FooterMobile from '../FooterMobile/FooterMobile';
import FooterSocials from '../FooterSocials/FooterSocials';

export default function Footer() {
  const { isSmallest, isMobile, isTablet } = useScreenSize();

  if (isSmallest || isMobile) {
    return <FooterMobile />;
  }

  return (
    <footer
      style={{
        paddingLeft: isSmallest || isMobile || isTablet ? '5px' : '0',
        paddingRight: isSmallest || isMobile || isTablet ? '5px' : '0',
      }}
    >
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
