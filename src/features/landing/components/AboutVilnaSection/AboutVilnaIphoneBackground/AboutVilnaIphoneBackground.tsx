import { Box } from '@mui/material';
import Image from 'next/image';
import React from 'react';

const styles = {
  box: {
    width: '100%',
    maxWidth: '100%',
    height: '328px',
    position: 'absolute',
    zIndex: '600',
    top: '-44px',
    overflow: 'clip',
  },
  image: {
    width: 'auto',
    height: 'auto',
    maxWidth: '100%',
  },
};

export default function AboutVilnaIphoneBackground({ style }: { style?: React.CSSProperties }) {
  return (
    <Box sx={{ ...styles.box, ...style }}>
      <Image
        width={1450}
        height={2936}
        src="/assets/img/AboutVilna/iphone_picture.png"
        alt="Iphone Background"
        style={{ ...styles.image }}
      />
    </Box>
  );
}

AboutVilnaIphoneBackground.defaultProps = {
  style: {},
};
