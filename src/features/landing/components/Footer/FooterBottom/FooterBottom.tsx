import { Box, Container } from '@mui/material';
import React from 'react';

const styles = {
  mainBox: {
    background: '#F4F5F6',
    borderRadius: '16px 16px 0px 0px',
  },
  mainContainer: {
    width: '100%',
    maxWidth: '1192px',
    margin: '0 auto',
    padding: '12px 0 13px 0',
    display: 'flex',
    justifyContent: 'space-between',
  },
};

export default function FooterBottom({ children }: { children: React.ReactNode }) {
  return (
    <Box
      sx={{
        ...styles.mainBox,
      }}
    >
      <Container
        sx={{
          ...styles.mainContainer,
        }}
      >
        {children}
      </Container>
    </Box>
  );
}
