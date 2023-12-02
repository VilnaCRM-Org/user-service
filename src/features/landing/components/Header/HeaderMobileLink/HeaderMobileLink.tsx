import { Grid, Typography } from '@mui/material';
import { LinkProps } from 'next/link';

import CustomLink from '@/components/ui/CustomLink/CustomLink';

import ArrowDownIcon from '../../Icons/ArrowDownIcon/ArrowDownIcon';

interface IHeaderMobileLinkProps extends LinkProps {
  href: string;
  linkNameText: string;
}

const headerMobileLinkStyle: React.CSSProperties = {
  display: 'inline-block',
  marginBottom: '6px',
  width: '100%',
};

const innerLinkContainerStyle: React.CSSProperties = {
  display: 'flex',
  justifyContent: 'space-between',
  alignItems: 'center',
  borderRadius: '8px',
  background: '#F5F6F7',
  width: '100%',
  padding: '19px 20px',
};

const typographyLinkStyles: React.CSSProperties = {
  fontFamily: 'inherit',
  fontSize: '18px',
  fontStyle: 'normal',
  fontWeight: '600',
  lineHeight: 'normal',
};

export default function HeaderMobileLink({ href, linkNameText }: IHeaderMobileLinkProps) {
  return (
    <CustomLink href={href} style={headerMobileLinkStyle}>
      <Grid container sx={innerLinkContainerStyle}>
        <Grid item>
          <Typography sx={typographyLinkStyles}>{linkNameText}</Typography>
        </Grid>
        <Grid item>
          <ArrowDownIcon />
        </Grid>
      </Grid>
    </CustomLink>
  );
}
