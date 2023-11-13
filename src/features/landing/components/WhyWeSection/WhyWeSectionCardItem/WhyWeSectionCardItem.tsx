import { useState } from 'react';
import { Card, Icon, Typography } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { IWhyWeCardItem } from '@/features/landing/types/why-we/types';

interface IWhyWeSectionItemCardItemProps {
  cardItem: IWhyWeCardItem;
}

const cartItemStyle: React.CSSProperties = {
  width: '100%',
  height: '100%',
  borderRadius: '12px',
  border: '1px solid #D0D4D8',
  background: '#FFF',
  transition: 'box-shadow 0.3s ease-in-out',
  display: 'flex',
  padding: '24px 26px 66px 24px',
  flexDirection: 'column',
  alignItems: 'flex-start',
  gap: '13px',
};

export function WhyWeSectionCardItem({ cardItem }: IWhyWeSectionItemCardItemProps) {
  const { imageSrc, title, text } = cardItem;
  const { t } = useTranslation();
  const [isHovered, setIsHovered] = useState(false);

  const handleMouseOver = () => {
    setIsHovered(true);
  };

  const handleMouseOut = () => {
    setIsHovered(false);
  };

  return (
    <Card
      onMouseOver={handleMouseOver}
      onMouseOut={handleMouseOut}
      sx={{
        ...cartItemStyle,
        boxShadow: isHovered ? '0px 8px 27px 0px rgba(49, 59, 67, 0.14)' : 'none',
        cursor: isHovered ? 'pointer' : 'default',
      }}>
      <Icon sx={{width: '100%', maxWidth: '70px', height: '70px'}}>
        <img src={imageSrc} alt={title} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
      </Icon>
      <Typography
        variant={'h3'}
        component={'h3'}
        sx={{
          color: '#1A1C1E',
          textAlign: 'left',
          fontFamily: 'GolosText-Regular, sans-serif',
          fontSize: '28px',
          fontStyle: 'normal',
          fontWeight: 700,
          lineHeight: 'normal',
        }}>
        {t(title)}
      </Typography>
      <Typography variant={'body1'} component={'p'} sx={{
        color: '#1A1C1E',
        fontFamily: 'GolosText-Regular, sans-serif',
        fontSize: '18px',
        fontStyle: 'normal',
        fontWeight: 400,
        lineHeight: '30px',
      }}>
        {t(text)}
      </Typography>
    </Card>
  );

}
