import { Grid } from '@mui/material';
import React, { CSSProperties, useEffect, useRef } from 'react';
import { Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/react';

import 'swiper/css';
import 'swiper/css/pagination';
import CardItem from '../UiCardItem';

import styles from './styles';
import { CardList } from './types';

function CardGrid({ cardList, imageList }: CardList): React.ReactElement {
  const grid: CSSProperties =
    cardList[0].type === 'smallCard' ? styles.smallGrid : styles.largeGrid;

  return (
    <Grid sx={grid}>
      {cardList.map(item => (
        <CardItem key={item.id} item={item} imageList={imageList || []} />
      ))}
    </Grid>
  );
}
function CardSwiper({ cardList, imageList }: CardList): React.ReactElement {
  const swiperRef: React.RefObject<HTMLElement> = useRef(null);

  useEffect(() => {
    const target: HTMLElement | null = document.querySelector('body');

    function isToolTip(node: Element): boolean {
      return (
        node.role === 'tooltip' && node.classList.contains('base-Popper-root')
      );
    }

    const config: MutationObserverInit = {
      childList: true,
    };

    const observer: MutationObserver = new MutationObserver(
      (mutationsList: MutationRecord[]) => {
        mutationsList.forEach((mutation: MutationRecord) => {
          if (mutation.type === 'childList') {
            mutation.addedNodes.forEach((node: Node): void => {
              if (node instanceof Element && isToolTip(node)) {
                swiperRef.current!.style.pointerEvents = 'none';
              }
            });
            mutation.removedNodes.forEach((node: Node): void => {
              if (node instanceof Element && isToolTip(node)) {
                swiperRef.current!.style.pointerEvents = 'auto';
              }
            });
          }
        });
      }
    );

    if (target) {
      observer.observe(target, config);
    }

    return () => observer.disconnect();
  }, []);

  const gridMobile: CSSProperties =
    cardList[0].type === 'smallCard'
      ? styles.gridSmallMobile
      : styles.gridLargeMobile;

  return (
    <Grid sx={gridMobile} ref={swiperRef as React.RefObject<HTMLDivElement>}>
      <Swiper
        pagination
        modules={[Pagination]}
        spaceBetween={12}
        slidesPerView={1.04}
        loop
        className="swiper-wrapper"
      >
        {cardList.map(item => (
          <SwiperSlide key={item.id}>
            <CardItem item={item} imageList={imageList} />
          </SwiperSlide>
        ))}
      </Swiper>
    </Grid>
  );
}
function UiCardList({ cardList, imageList }: CardList): React.ReactElement {
  return (
    <>
      <CardGrid cardList={cardList} imageList={imageList} />
      <CardSwiper cardList={cardList} imageList={imageList} />
    </>
  );
}
export default UiCardList;