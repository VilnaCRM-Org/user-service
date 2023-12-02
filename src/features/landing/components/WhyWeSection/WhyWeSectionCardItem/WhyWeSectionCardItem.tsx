import { Card, Icon, Typography } from '@mui/material';
import Image from 'next/image';
import React, { useState } from 'react';
import ReactHtmlParser from 'react-html-parser';
import { useTranslation } from 'react-i18next';

import useScreenSize from '../../../hooks/useScreenSize/useScreenSize';
import IWhyWeCardItem from '../../../types/why-we/types';
import { TRANSLATION_NAMESPACE } from '../../../utils/constants/constants';

interface IWhyWeSectionItemCardItemProps {
  cardItem: IWhyWeCardItem;
  style?: React.CSSProperties;
  isSmall?: boolean;
}

const styles = {
  cardItem: {
    width: '100%',
    height: '100%',
    maxHeight: '21.375rem', // 342px
    borderRadius: '12px',
    border: '1px solid #D0D4D8',
    background: '#FFF',
    transition: 'box-shadow 0.3s ease-in-out',
    display: 'flex',
    padding: '24px 22px 32px 24px',
    flexDirection: 'column',
    alignItems: 'flex-start',
    gap: '13px',
  },
  cardItemLaptopOrLower: {
    minHeight: '23.75rem', // 380px
  },
  cardItemMobileOrSmallest: {
    minHeight: '16.4375rem', // 263px
  },
  icon: {
    width: '100%',
    maxWidth: '70px',
    height: '70px',
  },
  image: {
    width: '100%',
    height: '100%',
    maxWidth: '100%',
    objectFit: 'contain',
    pointerEvents: 'none',
    userSelect: 'none',
  },
  title: {
    color: '#1A1C1E',
    textAlign: 'left',
    fontFamily: 'GolosText-Bold, sans-serif',
    fontSize: '28px',
    fontStyle: 'normal',
    fontWeight: 700,
    lineHeight: 'normal',
  },
  titleLaptopOrLower: {
    fontSize: '22px',
  },
  titleMobileOrSmallest: {
    fontSize: '18px',
    fontWeight: '600',
    paddingTop: '3px',
  },
  text: {
    color: '#1A1C1E',
    fontFamily: 'GolosText-Regular, sans-serif',
    fontSize: '18px',
    fontStyle: 'normal',
    fontWeight: 400,
    lineHeight: '30px',
  },
  textMobileOrSmallest: {
    fontSize: '15px',
    lineHeight: '25px',
  },
};

export default function WhyWeSectionCardItem({
  cardItem,
  style,
  isSmall,
}: IWhyWeSectionItemCardItemProps) {
  const { imageSrc, title, text } = cardItem;
  const { t } = useTranslation(TRANSLATION_NAMESPACE);
  const [isHovered, setIsHovered] = useState(false);
  const { isLaptop, isSmallest, isMobile, isTablet } = useScreenSize();

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
        ...styles.cardItem,
        ...(isLaptop || isTablet ? styles.cardItemLaptopOrLower : {}),
        ...(isMobile || isSmallest ? styles.cardItemMobileOrSmallest : {}),
        boxShadow: isHovered ? '0px 8px 27px 0px rgba(49, 59, 67, 0.14)' : 'none',
        cursor: isHovered ? 'pointer' : 'default',
        ...style,
      }}
    >
      <Icon sx={{ ...styles.icon, ...(isSmall ? { width: '50px', height: '50px' } : {}) }}>
        <Image
          width={140}
          height={140}
          draggable={false}
          src={imageSrc}
          alt={title}
          style={{
            ...styles.image,
            objectFit: 'cover',
            pointerEvents: 'none',
            userSelect: 'none',
          }}
        />
      </Icon>
      <Typography
        style={{
          ...styles.title,
          ...(isLaptop || isTablet ? styles.titleLaptopOrLower : {}),
          ...(isMobile || isSmallest ? styles.titleMobileOrSmallest : {}),
          textAlign: 'left',
        }}
      >
        {t(title)}
      </Typography>
      <Typography
        style={{
          ...styles.text,
          ...(isMobile || isSmallest ? styles.textMobileOrSmallest : {}),
        }}
      >
        {ReactHtmlParser(t(text))}
      </Typography>
    </Card>
  );
}

WhyWeSectionCardItem.defaultProps = {
  style: {},
  isSmall: false,
};
