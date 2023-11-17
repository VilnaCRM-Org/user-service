import { Box, Typography } from '@mui/material';

import CustomLink from '../CustomLink/CustomLink';

interface ISocialLinkProps {
  icon: string;
  title: string;
  linkHref: string;
}

const styles = {
  main: {
    display: 'inline-flex',
    padding: '18px 47.5px 18px 48.5px',
    justifyContent: 'center',
    alignItems: 'center',
    gap: '9px',
    borderRadius: '12px',
    border: '1px solid #E1E7EA',
    background: '#FFF',
  },
  imageBox: {
    width: '100%', maxWidth: '22px',
  },
  image: {
    width: '100%',
    maxWidth: '22px',
  },
  text: {
    color: '#1A1C1E',
    fontFamily: 'GolosText-Regular, sans-serif',
    fontSize: '18px',
    fontStyle: 'normal',
    fontWeight: '600',
    lineHeight: 'normal',
  },
};

export function SocialLink({ icon, title, linkHref }: ISocialLinkProps) {
  return (
    <CustomLink href={linkHref} style={{
      ...styles.main,
    }}>
      <Box sx={{ ...styles.imageBox }}>
        <img src={icon} alt={title}
             style={{
               ...styles.image,
               objectFit: 'cover',
             }} />
      </Box>
      <Typography variant="body1" component="p" sx={{
        ...styles.text,
      }}>{title}</Typography>
    </CustomLink>
  );
}
