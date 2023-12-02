import { Box } from '@mui/material';
import Image from 'next/image';
import React from 'react';

export default function AboutVilnaMainCRMImage({
  imageSrc,
  imageAltText,
  style,
}: {
  imageSrc: string;
  imageAltText: string;
  style?: React.CSSProperties;
}) {
  return (
    <Box
      sx={{
        height: '100%',
        maxHeight: '31.1466875rem', // 498.347px
        width: '100%',
        maxWidth: '47.91796125rem', // 766.68738px
        display: 'block',
        position: 'absolute',
        bottom: '1.76375rem', // 28.22px
        left: '1.15875rem', // 18.54px
        zIndex: '750',
        overflow: 'hidden',
        borderRadius: '12px 12px 0 0',
        ...style,
      }}
    >
      <Image
        width={2000}
        height={1300}
        src={imageSrc}
        alt={imageAltText}
        style={{ width: 'auto', height: 'auto', maxWidth: '100%', display: 'block' }}
      />
    </Box>
  );
}

AboutVilnaMainCRMImage.defaultProps = {
  style: {},
};
