import { LinkProps } from 'next/link';
import { Grid, Typography } from '@mui/material';

import { CustomLink } from '@/components/ui/CustomLink/CustomLink';
import ArrowDownIcon from '@/features/landing/components/Icons/ArrowDownIcon/ArrowDownIcon';

interface IHeaderMobileLinkProps extends LinkProps {
  href: string;
  linkNameText: string;
}

const headerMobileLinkStyle: React.CSSProperties = {
  display: 'inline-block',
  marginBottom: '6px',
};

const innerLinkContainerStyle: React.CSSProperties = {
  display: 'flex',
  justifyContent: 'space-between',
  borderRadius: '8px',
  background: '#F5F6F7',
  width: '100%',
};

export function HeaderMobileLink({ href, linkNameText }: IHeaderMobileLinkProps) {
  return (
    <CustomLink href={href} style={headerMobileLinkStyle}>
      <Grid container
            sx={innerLinkContainerStyle}>
        <Grid item sx={{ display: 'flex', width: '100%', padding: '19px 20px' }}>
          <Typography>{linkNameText}</Typography>
          <ArrowDownIcon />
        </Grid>
      </Grid>
    </CustomLink>
  );
}
